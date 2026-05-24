<?php

declare(strict_types=1);

namespace Shahmy\LaravelDddToolkit\Support;

/**
 * NamespaceGenerator — derives PSR-4 namespaces from the DDD project structure.
 *
 * All namespace generation logic is centralised here so that Artisan commands
 * and generators produce consistent, predictable namespace strings.
 */
final class NamespaceGenerator
{
    public function __construct(
        private readonly string $baseNamespace = 'Src',
        private readonly string $srcDirectory  = 'src',
    ) {}

    // ─── Namespace Derivation ─────────────────────────────────────────────

    /**
     * Full namespace for a domain root.
     *
     * Example: forDomain('Merchant') → "Src\\Merchant"
     */
    public function forDomain(string $domain): string
    {
        return "{$this->baseNamespace}\\{$domain}";
    }

    /**
     * Full namespace for a domain layer.
     *
     * Example: forLayer('Merchant', 'Domain') → "Src\\Merchant\\Domain"
     */
    public function forLayer(string $domain, string $layer): string
    {
        return "{$this->baseNamespace}\\{$domain}\\{$layer}";
    }

    /**
     * Full namespace for a class at an arbitrary depth inside a domain.
     *
     * Example: forClass('Merchant', 'Domain', 'Entities') → "Src\\Merchant\\Domain\\Entities"
     */
    public function forClass(string $domain, string ...$layers): string
    {
        $parts = array_filter([$this->baseNamespace, $domain, ...$layers], fn($p) => $p !== '');
        return implode('\\', $parts);
    }

    /**
     * Convert a relative path inside src/ to a namespace.
     *
     * Example: pathToNamespace('Merchant/Domain/Entities') → "Src\\Merchant\\Domain\\Entities"
     */
    public function pathToNamespace(string $relativePath): string
    {
        $normalized = str_replace(['/', '\\'], '\\', $relativePath);
        return "{$this->baseNamespace}\\{$normalized}";
    }

    /**
     * Convert a namespace back to a relative filesystem path.
     *
     * Example: namespaceToPath('Src\\Merchant\\Domain\\Entities') → "src/Merchant/Domain/Entities"
     */
    public function namespaceToPath(string $namespace): string
    {
        $withoutBase = ltrim(str_replace($this->baseNamespace, '', $namespace), '\\');
        return $this->srcDirectory . '/' . str_replace('\\', '/', $withoutBase);
    }

    /**
     * The PSR-4 autoload entry for the src/ directory.
     *
     * @return array{namespace: string, path: string}
     */
    public function getSrcAutoloadEntry(): array
    {
        return [
            'namespace' => "{$this->baseNamespace}\\",
            'path'      => "{$this->srcDirectory}/",
        ];
    }

    // ─── Getters ──────────────────────────────────────────────────────────

    public function getBaseNamespace(): string
    {
        return $this->baseNamespace;
    }

    public function getSrcDirectory(): string
    {
        return $this->srcDirectory;
    }
}
