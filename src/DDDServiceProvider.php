<?php

declare(strict_types=1);

namespace Shahmy\LaravelDddToolkit;

use Shahmy\LaravelDddToolkit\Commands\DddActionCommand;
use Shahmy\LaravelDddToolkit\Commands\DddDomainCommand;
use Shahmy\LaravelDddToolkit\Commands\DddDtoCommand;
use Shahmy\LaravelDddToolkit\Commands\DddEntityCommand;
use Shahmy\LaravelDddToolkit\Commands\DddEnumCommand;
use Shahmy\LaravelDddToolkit\Commands\DddEventCommand;
use Shahmy\LaravelDddToolkit\Commands\DddInitCommand;
use Shahmy\LaravelDddToolkit\Commands\DddPolicyCommand;
use Shahmy\LaravelDddToolkit\Commands\DddRepositoryCommand;
use Shahmy\LaravelDddToolkit\Commands\DddRequestCommand;
use Shahmy\LaravelDddToolkit\Commands\DddResourceCommand;
use Shahmy\LaravelDddToolkit\Commands\DddServiceCommand;
use Shahmy\LaravelDddToolkit\Commands\DddTestCommand;
use Shahmy\LaravelDddToolkit\Commands\DddUseCaseCommand;
use Shahmy\LaravelDddToolkit\Commands\DddValueObjectCommand;
use Shahmy\LaravelDddToolkit\Generators\ClassGenerator;
use Shahmy\LaravelDddToolkit\Generators\DomainGenerator;
use Shahmy\LaravelDddToolkit\Support\AtomicFilesystem;
use Shahmy\LaravelDddToolkit\Support\ComposerUpdater;
use Shahmy\LaravelDddToolkit\Support\NamespaceGenerator;
use Shahmy\LaravelDddToolkit\Support\StubEngine;
use Illuminate\Support\ServiceProvider;

/**
 * Laravel DDD Toolkit Service Provider.
 *
 * Registers all Artisan commands, support classes, and generators
 * into the Laravel service container. Handles config and stub publishing.
 */
final class DDDServiceProvider extends ServiceProvider
{
    // ─── Registration ─────────────────────────────────────────────────────

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/ddd.php', 'ddd');

        $this->registerSupportClasses();
        $this->registerGenerators();
    }

    // ─── Boot ─────────────────────────────────────────────────────────────

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->registerCommands();
            $this->registerPublishables();
        }
    }

    // ─── Private ──────────────────────────────────────────────────────────

    private function registerSupportClasses(): void
    {
        $this->app->singleton(AtomicFilesystem::class);

        $this->app->singleton(StubEngine::class, function (): StubEngine {
            $customPath = config('ddd.stubs_path');
            $defaultPath = __DIR__ . '/../stubs';

            // Auto-detect published stubs in project
            $publishedPath = base_path('stubs/ddd');

            return new StubEngine(
                $customPath ?? (is_dir($publishedPath) ? $publishedPath : $defaultPath),
                $this->app->make(AtomicFilesystem::class),
            );
        });

        $this->app->singleton(ComposerUpdater::class, function (): ComposerUpdater {
            return new ComposerUpdater(
                base_path('composer.json'),
                $this->app->make(AtomicFilesystem::class),
            );
        });

        $this->app->singleton(NamespaceGenerator::class, function (): NamespaceGenerator {
            return new NamespaceGenerator(
                (string) config('ddd.base_namespace', 'Src'),
                (string) config('ddd.src_directory', 'src'),
            );
        });
    }

    private function registerGenerators(): void
    {
        $this->app->singleton(DomainGenerator::class, function (): DomainGenerator {
            return new DomainGenerator(
                $this->app->make(AtomicFilesystem::class),
                $this->app->make(StubEngine::class),
                $this->app->make(NamespaceGenerator::class),
                (array) config('ddd.layers', []),
            );
        });

        $this->app->singleton(ClassGenerator::class, function (): ClassGenerator {
            return new ClassGenerator(
                $this->app->make(StubEngine::class),
                $this->app->make(NamespaceGenerator::class),
                $this->app->make(AtomicFilesystem::class),
            );
        });
    }

    private function registerCommands(): void
    {
        $this->commands([
            DddInitCommand::class,
            DddDomainCommand::class,
            DddEntityCommand::class,
            DddRepositoryCommand::class,
            DddServiceCommand::class,
            DddDtoCommand::class,
            DddActionCommand::class,
            DddUseCaseCommand::class,
            DddEventCommand::class,
            DddValueObjectCommand::class,
            DddEnumCommand::class,
            DddPolicyCommand::class,
            DddResourceCommand::class,
            DddRequestCommand::class,
            DddTestCommand::class,
        ]);
    }

    private function registerPublishables(): void
    {
        $this->publishes(
            [__DIR__ . '/../config/ddd.php' => config_path('ddd.php')],
            'ddd-config',
        );

        $this->publishes(
            [__DIR__ . '/../stubs' => base_path('stubs/ddd')],
            'ddd-stubs',
        );
    }
}
