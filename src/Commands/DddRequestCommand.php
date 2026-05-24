<?php

declare(strict_types=1);

namespace Shahmy\LaravelDddToolkit\Commands;

use Shahmy\LaravelDddToolkit\Generators\ClassGenerator;
use Illuminate\Console\Command;

/** ddd:request — Create a new Form Request in Presentation/API/Requests. */
final class DddRequestCommand extends Command
{
    protected $signature = 'ddd:request
                            {domain : Domain name (PascalCase)}
                            {name : Request class name (PascalCase)}
                            {--force} {--dry-run}';

    protected $description = 'Create a new Form Request in Presentation/API/Requests';

    public function handle(ClassGenerator $generator): int
    {
        $domain    = (string) $this->argument('domain');
        $name      = (string) $this->argument('name');
        $className = str_ends_with($name, 'Request') ? $name : "{$name}Request";

        if (! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $domain) || ! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name)) {
            $this->error('Domain and class names must be PascalCase.');
            return self::FAILURE;
        }

        try {
            $path = $generator->generate('request', $domain, 'Presentation/API', 'Requests', $className, [], (bool) $this->option('force'), (bool) $this->option('dry-run'));
            $relative = str_replace(base_path() . '/', '', $path);
            $this->line((bool) $this->option('dry-run') ? "<fg=cyan>[dry-run]</> Would create: {$relative}" : "<fg=green>✓</> Created: {$relative}");
            if (! (bool) $this->option('dry-run')) {
                $this->info("✅ Request '{$className}' created in {$domain}/Presentation/API/Requests/");
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
