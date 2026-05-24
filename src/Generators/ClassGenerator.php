<?php

declare(strict_types=1);

namespace Shahmy\LaravelDddToolkit\Generators;

use Shahmy\LaravelDddToolkit\Support\AtomicFilesystem;
use Shahmy\LaravelDddToolkit\Support\NamespaceGenerator;
use Shahmy\LaravelDddToolkit\Support\StubEngine;
use Illuminate\Filesystem\Filesystem;

/**
 * ClassGenerator — generates a single PHP class file from a stub template.
 *
 * Used by all Artisan commands that create individual classes
 * (entities, repositories, services, DTOs, use cases, events, etc.)
 *
 * The src path is resolved at generation time from config (see DomainGenerator::resolveSrcPath)
 * so that tests can set ddd.src_directory to an absolute path without altering base_path().
 */
final class ClassGenerator
{
    private readonly Filesystem $files;

    public function __construct(
        private readonly StubEngine         $stubEngine,
        private readonly NamespaceGenerator $namespaceGen,
        private readonly AtomicFilesystem   $atomicFs,
    ) {
        $this->files = new Filesystem();
    }

    // ─── Public API ───────────────────────────────────────────────────────

    /**
     * Generate a PHP class file from a stub template.
     *
     * @param array<string, string> $extraVars  Additional template variables
     *
     * @return string The absolute path of the generated file
     *
     * @throws \RuntimeException If the file exists (and force=false) or generation fails
     */
    public function generate(
        string $stubName,
        string $domain,
        string $layer,
        string $subfolder,
        string $className,
        array  $extraVars = [],
        bool   $force     = false,
        bool   $dryRun    = false,
    ): string {
        $filePath  = $this->buildFilePath($domain, $layer, $subfolder, $className);
        $namespace = $this->namespaceGen->forClass($domain, $layer, $subfolder);

        if ($dryRun) {
            return $filePath;
        }

        if ($this->files->exists($filePath) && ! $force) {
            throw new \RuntimeException(
                "File already exists: {$filePath}. Use --force to overwrite.",
            );
        }

        $variables = array_merge([
            'NAMESPACE'  => $namespace,
            'CLASS_NAME' => $className,
            'DOMAIN'     => $domain,
            'LAYER'      => $layer,
        ], $extraVars);

        $this->stubEngine->renderToFile($stubName, $variables, $filePath, $force);

        return $filePath;
    }

    /**
     * Build the absolute file path for a class.
     */
    public function buildFilePath(
        string $domain,
        string $layer,
        string $subfolder,
        string $className,
    ): string {
        // Support nested layer paths: 'Infrastructure/Persistence' → Infrastructure\Persistence\
        $layerPath = str_replace('/', DIRECTORY_SEPARATOR, $layer);

        return implode(DIRECTORY_SEPARATOR, array_filter([
            DomainGenerator::resolveSrcPath(),
            $domain,
            $layerPath,
            $subfolder,
            "{$className}.php",
        ]));
    }

    /**
     * Derive the namespace for a class at the given location.
     */
    public function buildNamespace(string $domain, string $layer, string $subfolder = ''): string
    {
        return $this->namespaceGen->forClass($domain, $layer, $subfolder);
    }
}
