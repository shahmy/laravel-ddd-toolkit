<?php

declare(strict_types=1);

namespace Shahmy\LaravelDddToolkit\Commands;

use Shahmy\LaravelDddToolkit\Generators\ClassGenerator;
use Shahmy\LaravelDddToolkit\Support\NamespaceGenerator;
use Illuminate\Console\Command;

/**
 * ddd:repository — Create both a Repository interface (Domain layer) and
 * its Eloquent implementation (Infrastructure layer).
 */
final class DddRepositoryCommand extends Command
{
    protected $signature = 'ddd:repository
                            {domain : Domain name (PascalCase)}
                            {name : Repository name (PascalCase, without "Repository" suffix)}
                            {--force : Overwrite existing files}
                            {--dry-run : Preview without creating files}
                            {--interface-only : Only generate the interface, skip the implementation}
                            {--implementation-only : Only generate the Eloquent implementation}';

    protected $description = 'Create a Repository interface (Domain) and Eloquent implementation (Infrastructure)';

    public function handle(ClassGenerator $generator, NamespaceGenerator $namespaceGen): int
    {
        $domain  = (string) $this->argument('domain');
        $name    = (string) $this->argument('name');
        $force   = (bool) $this->option('force');
        $dryRun  = (bool) $this->option('dry-run');

        // Strip trailing "Repository" suffix — the command appends it automatically.
        // Handles: ddd:repository Merchant MerchantRepository → treated as "Merchant"
        $name = (string) preg_replace('/Repository$/i', '', $name);

        if (! $this->validateNames($domain, $name)) {
            return self::FAILURE;
        }

        $interfaceOnly      = (bool) $this->option('interface-only');
        $implementationOnly = (bool) $this->option('implementation-only');

        try {
            // ── Interface (Domain/Repositories) ──────────────────────────

            if (! $implementationOnly) {
                $interfacePath = $generator->generate(
                    'repository-interface',
                    $domain,
                    'Domain',
                    'Repositories',
                    "{$name}RepositoryInterface",
                    [],
                    $force,
                    $dryRun,
                );
                $this->printResult($interfacePath, $dryRun, 'Interface');
            }

            // ── Implementation (Infrastructure/Persistence/Repositories) ─

            if (! $interfaceOnly) {
                $implNamespace = $namespaceGen->forClass($domain, 'Domain', 'Repositories');
                $implPath      = $generator->generate(
                    'repository',
                    $domain,
                    'Infrastructure/Persistence',
                    'Repositories',
                    "Eloquent{$name}Repository",
                    [
                        'CLASS_NAME'         => $name,
                        'INTERFACE_NAMESPACE' => $implNamespace,
                        'ENTITY_NAMESPACE'    => $namespaceGen->forClass($domain, 'Domain', 'Entities'),
                        'ENTITY_NAME'         => $name,
                    ],
                    $force,
                    $dryRun,
                );
                $this->printResult($implPath, $dryRun, 'Implementation');
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        if (! $dryRun) {
            $this->newLine();
            $this->info("✅ Repository '{$name}' created in domain '{$domain}'");
            $this->comment("  Register the binding in {$domain}/Infrastructure/Providers/{$domain}ServiceProvider.php:");
            $this->line("  \$this->app->bind({$name}RepositoryInterface::class, Eloquent{$name}Repository::class);");
        }

        return self::SUCCESS;
    }

    private function validateNames(string $domain, string $name): bool
    {
        if (! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $domain)) {
            $this->error("Domain '{$domain}' must be PascalCase.");
            return false;
        }
        if (! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name)) {
            $this->error("Repository name '{$name}' must be PascalCase.");
            return false;
        }
        return true;
    }

    private function printResult(string $path, bool $dryRun, string $label): void
    {
        $relative = str_replace(base_path() . '/', '', $path);
        $prefix   = $dryRun ? '<fg=cyan>[dry-run]</> Would create' : '<fg=green>✓</> Created';
        $this->line("  {$prefix} {$label}: {$relative}");
    }
}
