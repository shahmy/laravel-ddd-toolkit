<?php

declare(strict_types=1);

namespace Shahmy\LaravelDddToolkit\Commands;

use Shahmy\LaravelDddToolkit\Generators\ClassGenerator;
use Illuminate\Console\Command;

/** ddd:event — Create a new Domain Event in Domain/Events. */
final class DddEventCommand extends Command
{
    protected $signature = 'ddd:event
                            {domain : Domain name (PascalCase)}
                            {name : Event class name in past tense (e.g. ProductCreated)}
                            {--force} {--dry-run}';

    protected $description = 'Create a new Domain Event (name in past tense, e.g. ProductCreated)';

    public function handle(ClassGenerator $generator): int
    {
        $domain = (string) $this->argument('domain');
        $name   = (string) $this->argument('name');

        if (! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $domain) || ! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name)) {
            $this->error('Domain and class names must be PascalCase.');
            return self::FAILURE;
        }

        try {
            $path = $generator->generate('event', $domain, 'Domain', 'Events', $name, [], (bool) $this->option('force'), (bool) $this->option('dry-run'));
            $relative = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path);
            $this->line((bool) $this->option('dry-run') ? "<fg=cyan>[dry-run]</> Would create: {$relative}" : "<fg=green>✓</> Created: {$relative}");
            if (! (bool) $this->option('dry-run')) {
                $this->info("✅ Domain Event '{$name}' created in {$domain}/Domain/Events/");
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
