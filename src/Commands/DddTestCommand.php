<?php

declare(strict_types=1);

namespace Shahmy\LaravelDddToolkit\Commands;

use Shahmy\LaravelDddToolkit\Generators\ClassGenerator;
use Illuminate\Console\Command;

/**
 * ddd:test — Create a new test file inside a domain's Tests layer.
 *
 * Generates either a Pest or PHPUnit test depending on ddd.test_framework config.
 * Supports --feature and --unit flags to place in the correct subfolder.
 */
final class DddTestCommand extends Command
{
    protected $signature = 'ddd:test
                            {domain : Domain name (PascalCase)}
                            {name : Test class name (PascalCase, without "Test" suffix)}
                            {--feature : Generate a Feature test (default)}
                            {--unit : Generate a Unit test}
                            {--force} {--dry-run}';

    protected $description = 'Create a new Pest or PHPUnit test inside a domain\'s Tests layer';

    public function handle(ClassGenerator $generator): int
    {
        $domain    = (string) $this->argument('domain');
        $name      = (string) $this->argument('name');
        $force     = (bool) $this->option('force');
        $dryRun    = (bool) $this->option('dry-run');
        $isUnit    = (bool) $this->option('unit');
        $subfolder = $isUnit ? 'Unit' : 'Feature';
        $framework = (string) config('ddd.test_framework', 'pest');
        $stub      = $framework === 'phpunit' ? 'test-phpunit' : 'test-pest';
        $className = str_ends_with($name, 'Test') ? $name : "{$name}Test";

        if (! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $domain) || ! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name)) {
            $this->error('Domain and class names must be PascalCase.');
            return self::FAILURE;
        }

        try {
            $path = $generator->generate(
                $stub,
                $domain,
                'Tests',
                $subfolder,
                $className,
                ['TESTED_CLASS' => "Src\\{$domain}\\Domain\\Entities\\{$name}"],
                $force,
                $dryRun,
            );

            $relative = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path);
            $this->line($dryRun ? "<fg=cyan>[dry-run]</> Would create: {$relative}" : "<fg=green>✓</> Created: {$relative}");

            if (! $dryRun) {
                $frameworkLabel = $framework === 'phpunit' ? 'PHPUnit' : 'Pest';
                $this->info("✅ {$frameworkLabel} {$subfolder} test '{$className}' created in {$domain}/Tests/{$subfolder}/");
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
