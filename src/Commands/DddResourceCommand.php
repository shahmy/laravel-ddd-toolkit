<?php

declare(strict_types=1);

namespace Shahmy\LaravelDddToolkit\Commands;

use Shahmy\LaravelDddToolkit\Generators\ClassGenerator;
use Illuminate\Console\Command;

/** ddd:resource — Create a new Laravel API Resource in Presentation/API/Resources. */
final class DddResourceCommand extends Command
{
    protected $signature = 'ddd:resource
                            {domain : Domain name (PascalCase)}
                            {name : Resource class name (PascalCase)}
                            {--force} {--dry-run}';

    protected $description = 'Create a new Laravel API Resource in Presentation/API/Resources';

    public function handle(ClassGenerator $generator): int
    {
        $domain    = (string) $this->argument('domain');
        $name      = (string) $this->argument('name');
        $className = str_ends_with($name, 'Resource') ? $name : "{$name}Resource";

        if (! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $domain) || ! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name)) {
            $this->error('Domain and class names must be PascalCase.');
            return self::FAILURE;
        }

        try {
            $path = $generator->generate('resource', $domain, 'Presentation/API', 'Resources', $className, [], (bool) $this->option('force'), (bool) $this->option('dry-run'));
            $relative = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path);
            $this->line((bool) $this->option('dry-run') ? "<fg=cyan>[dry-run]</> Would create: {$relative}" : "<fg=green>✓</> Created: {$relative}");
            if (! (bool) $this->option('dry-run')) {
                $this->info("✅ Resource '{$className}' created in {$domain}/Presentation/API/Resources/");
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
