<?php

declare(strict_types=1);

namespace Shahmy\LaravelDddToolkit\Commands;

use Shahmy\LaravelDddToolkit\Generators\ClassGenerator;
use Illuminate\Console\Command;

/** ddd:action — Create a new Action in Application/Actions. */
final class DddActionCommand extends Command
{
    protected $signature = 'ddd:action
                            {domain : Domain name (PascalCase)}
                            {name : Action class name (PascalCase)}
                            {--force} {--dry-run}';

    protected $description = 'Create a new Application Action';

    public function handle(ClassGenerator $generator): int
    {
        $domain = (string) $this->argument('domain');
        $name   = (string) $this->argument('name');

        if (! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $domain) || ! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name)) {
            $this->error('Domain and class names must be PascalCase.');
            return self::FAILURE;
        }

        try {
            $path = $generator->generate('action', $domain, 'Application', 'Actions', $name, [], (bool) $this->option('force'), (bool) $this->option('dry-run'));
            $relative = str_replace(base_path() . '/', '', $path);
            $this->line((bool) $this->option('dry-run') ? "<fg=cyan>[dry-run]</> Would create: {$relative}" : "<fg=green>✓</> Created: {$relative}");
            if (! (bool) $this->option('dry-run')) {
                $this->info("✅ Action '{$name}' created in {$domain}/Application/Actions/");
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
