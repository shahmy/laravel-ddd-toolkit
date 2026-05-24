<?php

declare(strict_types=1);

namespace Shahmy\LaravelDddToolkit\Commands;

use Shahmy\LaravelDddToolkit\Generators\ClassGenerator;
use Illuminate\Console\Command;

/** ddd:enum — Create a new PHP 8.1+ backed Enum in Domain/Enums. */
final class DddEnumCommand extends Command
{
    protected $signature = 'ddd:enum
                            {domain : Domain name (PascalCase)}
                            {name : Enum class name (PascalCase)}
                            {--force} {--dry-run}';

    protected $description = 'Create a new Domain Enum (PHP 8.1+ backed enum)';

    public function handle(ClassGenerator $generator): int
    {
        $domain = (string) $this->argument('domain');
        $name   = (string) $this->argument('name');

        if (! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $domain) || ! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name)) {
            $this->error('Domain and class names must be PascalCase.');
            return self::FAILURE;
        }

        try {
            $path = $generator->generate('enum', $domain, 'Domain', 'Enums', $name, [], (bool) $this->option('force'), (bool) $this->option('dry-run'));
            $relative = str_replace(base_path() . '/', '', $path);
            $this->line((bool) $this->option('dry-run') ? "<fg=cyan>[dry-run]</> Would create: {$relative}" : "<fg=green>✓</> Created: {$relative}");
            if (! (bool) $this->option('dry-run')) {
                $this->info("✅ Enum '{$name}' created in {$domain}/Domain/Enums/");
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
