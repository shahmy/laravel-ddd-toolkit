<?php

declare(strict_types=1);

namespace Shahmy\LaravelDddToolkit\Commands;

use Shahmy\LaravelDddToolkit\Generators\ClassGenerator;
use Illuminate\Console\Command;

/** ddd:policy — Create a new Laravel Policy in the Domain layer. */
final class DddPolicyCommand extends Command
{
    protected $signature = 'ddd:policy
                            {domain : Domain name (PascalCase)}
                            {name : Policy class name (PascalCase, without Policy suffix)}
                            {--force} {--dry-run}';

    protected $description = 'Create a new Laravel Policy inside a domain';

    public function handle(ClassGenerator $generator): int
    {
        $domain = (string) $this->argument('domain');
        $name   = (string) $this->argument('name');
        $className = str_ends_with($name, 'Policy') ? $name : "{$name}Policy";

        if (! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $domain) || ! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name)) {
            $this->error('Domain and class names must be PascalCase.');
            return self::FAILURE;
        }

        try {
            $path = $generator->generate('policy', $domain, 'Domain', 'Policies', $className, [], (bool) $this->option('force'), (bool) $this->option('dry-run'));
            $relative = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path);
            $this->line((bool) $this->option('dry-run') ? "<fg=cyan>[dry-run]</> Would create: {$relative}" : "<fg=green>✓</> Created: {$relative}");
            if (! (bool) $this->option('dry-run')) {
                $this->info("✅ Policy '{$className}' created in {$domain}/Domain/Policies/");
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
