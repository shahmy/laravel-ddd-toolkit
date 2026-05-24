<?php

declare(strict_types=1);

namespace Shahmy\LaravelDddToolkit\Commands;

use Shahmy\LaravelDddToolkit\Generators\ClassGenerator;
use Illuminate\Console\Command;

/**
 * ddd:entity — Create a new DDD Entity in the Domain/Entities layer.
 */
final class DddEntityCommand extends Command
{
    protected $signature = 'ddd:entity
                            {domain : Domain name (PascalCase)}
                            {name : Entity class name (PascalCase)}
                            {--force : Overwrite if file exists}
                            {--dry-run : Preview without creating files}';

    protected $description = 'Create a new Domain Entity';

    public function handle(ClassGenerator $generator): int
    {
        $domain  = (string) $this->argument('domain');
        $name    = (string) $this->argument('name');
        $force   = (bool) $this->option('force');
        $dryRun  = (bool) $this->option('dry-run');

        if (! $this->validateNames($domain, $name)) {
            return self::FAILURE;
        }

        try {
            $path = $generator->generate('entity', $domain, 'Domain', 'Entities', $name, [], $force, $dryRun);
            $this->outputResult($path, $dryRun, 'Entity', $domain, $name);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function validateNames(string $domain, string $name): bool
    {
        if (! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $domain)) {
            $this->error("Domain name '{$domain}' must be PascalCase.");
            return false;
        }
        if (! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name)) {
            $this->error("Entity name '{$name}' must be PascalCase.");
            return false;
        }
        return true;
    }

    private function outputResult(string $path, bool $dryRun, string $type, string $domain, string $name): void
    {
        $relative = str_replace(base_path() . '/', '', $path);

        if ($dryRun) {
            $this->line("<fg=cyan>[dry-run]</> Would create: {$relative}");
        } else {
            $this->line("<fg=green>✓</> Created {$type}: {$relative}");
            $this->newLine();
            $this->info("✅ {$name} created in {$domain}/Domain/Entities/");
        }
    }
}
