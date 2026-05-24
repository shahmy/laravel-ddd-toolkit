<?php

declare(strict_types=1);

namespace Shahmy\LaravelDddToolkit\Commands;

use Shahmy\LaravelDddToolkit\Generators\ClassGenerator;
use Illuminate\Console\Command;

/** ddd:usecase — Create a new Use Case in Application/UseCases. */
final class DddUseCaseCommand extends Command
{
    protected $signature = 'ddd:usecase
                            {domain : Domain name (PascalCase)}
                            {name : Use Case class name (PascalCase)}
                            {--force} {--dry-run}';

    protected $description = 'Create a new Application Use Case';

    public function handle(ClassGenerator $generator): int
    {
        $domain = (string) $this->argument('domain');
        $name   = (string) $this->argument('name');

        if (! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $domain) || ! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name)) {
            $this->error('Domain and class names must be PascalCase.');
            return self::FAILURE;
        }

        try {
            $path = $generator->generate('usecase', $domain, 'Application', 'UseCases', $name, [], (bool) $this->option('force'), (bool) $this->option('dry-run'));
            $relative = str_replace(base_path() . '/', '', $path);
            $this->line((bool) $this->option('dry-run') ? "<fg=cyan>[dry-run]</> Would create: {$relative}" : "<fg=green>✓</> Created: {$relative}");
            if (! (bool) $this->option('dry-run')) {
                $this->info("✅ Use Case '{$name}' created in {$domain}/Application/UseCases/");
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
