<?php

declare(strict_types=1);

namespace Shahmy\LaravelDddToolkit\Generators;

use Shahmy\LaravelDddToolkit\Support\AtomicFilesystem;
use Shahmy\LaravelDddToolkit\Support\NamespaceGenerator;
use Shahmy\LaravelDddToolkit\Support\StubEngine;
use Illuminate\Filesystem\Filesystem;

/**
 * DomainGenerator — creates the complete DDD domain directory tree atomically.
 *
 * Responsibilities:
 *   • Create all layer directories and their nested subfolders
 *   • Generate README.md and module.json for the domain
 *   • Add .gitkeep to every empty directory
 *   • Roll back atomically on any failure
 *
 * The src path is resolved at generation time from config so that tests can
 * override ddd.src_directory to an absolute temp path without needing to
 * manipulate the application's basePath.
 */
final class DomainGenerator
{
    private readonly Filesystem $files;

    public function __construct(
        private readonly AtomicFilesystem   $atomicFs,
        private readonly StubEngine         $stubEngine,
        private readonly NamespaceGenerator $namespaceGen,
        private readonly array              $layers,
    ) {
        $this->files = new Filesystem();
    }

    // ─── Public API ───────────────────────────────────────────────────────

    /**
     * Scaffold a complete DDD domain.
     *
     * @return array<int, string> The list of created directory paths
     *
     * @throws \RuntimeException If scaffolding fails (all changes rolled back)
     */
    public function generate(string $domainName, bool $dryRun = false): array
    {
        $srcPath    = self::resolveSrcPath();
        $domainPath = $srcPath . DIRECTORY_SEPARATOR . $domainName;
        $namespace  = $this->namespaceGen->forDomain($domainName);

        if ($dryRun) {
            return $this->simulateStructure($domainPath);
        }

        $tx      = $this->atomicFs->beginTransaction();
        $created = [];

        try {
            // Domain root
            $this->atomicFs->stageDirectory($tx, $domainPath);
            $created[] = $domainPath;

            // Each layer and its subfolders
            foreach ($this->layers as $layerPath => $subfolders) {
                // Support nested paths like 'Infrastructure/Persistence' or 'Presentation/API'
                $parts   = explode('/', (string) $layerPath);
                $current = $domainPath;

                foreach ($parts as $part) {
                    $current .= DIRECTORY_SEPARATOR . $part;
                    $this->atomicFs->stageDirectory($tx, $current);
                    if (! in_array($current, $created, true)) {
                        $created[] = $current;
                    }
                }

                foreach ((array) $subfolders as $subfolder) {
                    $subPath = $current . DIRECTORY_SEPARATOR . (string) $subfolder;
                    $this->atomicFs->stageDirectory($tx, $subPath);
                    $created[] = $subPath;
                }
            }

            // README.md
            $readme = $this->generateReadme($domainName, $namespace);
            $this->atomicFs->stageFile($tx, $domainPath . '/README.md', $readme);

            // module.json
            $moduleJson = $this->generateModuleJson($domainName, $namespace);
            $this->atomicFs->stageFile($tx, $domainPath . '/module.json', $moduleJson);

            // Commit all staged operations
            $this->atomicFs->commit($tx);

            // Add .gitkeep files to every empty directory (post-commit, real FS)
            $this->addGitkeepsRecursively($domainPath);

            return $created;
        } catch (\Throwable $e) {
            $this->atomicFs->rollback($tx);
            throw new \RuntimeException(
                "Failed to generate domain '{$domainName}': {$e->getMessage()}",
                0,
                $e,
            );
        }
    }

    // ─── Static helpers ───────────────────────────────────────────────────

    /**
     * Resolve the absolute src directory from config.
     *
     * Supports absolute paths (useful in tests and custom setups) as well as
     * relative paths (resolved against base_path() in production).
     */
    public static function resolveSrcPath(): string
    {
        $dir = (string) config('ddd.src_directory', 'src');

        // Absolute Unix path or Windows drive letter path — use as-is
        if (str_starts_with($dir, '/') || (strlen($dir) > 1 && $dir[1] === ':')) {
            return rtrim($dir, '/\\');
        }

        return rtrim(base_path($dir), '/\\');
    }

    // ─── Private ──────────────────────────────────────────────────────────

    /**
     * @return array<int, string>
     */
    private function simulateStructure(string $domainPath): array
    {
        $paths = [$domainPath];

        foreach ($this->layers as $layerPath => $subfolders) {
            $parts   = explode('/', (string) $layerPath);
            $current = $domainPath;

            foreach ($parts as $part) {
                $current .= '/' . $part;
                if (! in_array($current, $paths, true)) {
                    $paths[] = $current;
                }
            }

            foreach ((array) $subfolders as $subfolder) {
                $paths[] = $current . '/' . (string) $subfolder;
            }
        }

        return $paths;
    }

    private function addGitkeepsRecursively(string $path): void
    {
        if (! $this->files->isDirectory($path)) {
            return;
        }

        foreach ($this->files->directories($path) as $dir) {
            $this->addGitkeepsRecursively($dir);
        }

        $this->atomicFs->ensureGitkeep($path);
    }

    private function generateReadme(string $domainName, string $namespace): string
    {
        return <<<MD
        # {$domainName} Domain

        **Namespace:** `{$namespace}`

        This directory contains the **{$domainName}** bounded context following Domain-Driven Design principles.

        ## Layer Structure

        | Layer | Purpose |
        |-------|---------|
        | `Application/` | Use cases, DTOs, application services, commands, queries, actions |
        | `Domain/` | Entities, value objects, domain events, repository interfaces, domain services |
        | `Infrastructure/` | Eloquent models, repository implementations, external services, caches |
        | `Presentation/` | HTTP controllers, API resources, form requests, routes, CLI commands |
        | `Tests/` | Feature and unit tests for this bounded context |

        ## Guidelines

        - **Domain layer** must have **zero** infrastructure dependencies (no Eloquent, no HTTP)
        - **Application layer** orchestrates domain objects and calls infrastructure via interfaces
        - **Infrastructure layer** implements domain contracts
        - Cross-domain communication goes through **Shared/**

        ## Artisan

        ```bash
        php artisan ddd:entity {$domainName} EntityName
        php artisan ddd:repository {$domainName} RepositoryName
        php artisan ddd:service {$domainName} ServiceName
        php artisan ddd:usecase {$domainName} UseCaseName
        ```

        MD;
    }

    private function generateModuleJson(string $domainName, string $namespace): string
    {
        return json_encode([
            'name'                  => $domainName,
            'namespace'             => $namespace,
            'version'               => '1.0.0',
            'description'           => "{$domainName} bounded context",
            'layers'                => ['Application', 'Domain', 'Infrastructure', 'Presentation', 'Tests'],
            'created_at'            => date('Y-m-d'),
            'ddd_toolkit_version'   => '1.0.0',
            'generator'             => 'shahmy/laravel-ddd-toolkit',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }
}
