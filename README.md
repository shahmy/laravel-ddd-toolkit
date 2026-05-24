# laravel-ddd-toolkit

**Artisan commands for enterprise Laravel Domain-Driven Design — scaffold complete bounded contexts in seconds.**

[![Packagist Version](https://img.shields.io/packagist/v/shahmy/laravel-ddd-toolkit?color=F28D1A)](https://packagist.org/packages/shahmy/laravel-ddd-toolkit)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4)](https://packagist.org/packages/shahmy/laravel-ddd-toolkit)
[![Laravel](https://img.shields.io/badge/Laravel-10%20|%2011%20|%2012%20|%2013-FF2D20)](https://packagist.org/packages/shahmy/laravel-ddd-toolkit)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Tests](https://github.com/shahmy/laravel-ddd-toolkit/actions/workflows/ci.yml/badge.svg)](https://github.com/shahmy/laravel-ddd-toolkit/actions)

---

## Overview

`laravel-ddd-toolkit` is a Composer package that brings a full suite of `ddd:*` Artisan commands to any Laravel application. It generates the complete DDD layer structure — Application, Domain, Infrastructure, Presentation, and Tests — along with typed PHP 8.3+ class stubs for every common DDD building block.

Works standalone from the command line. Pairs seamlessly with the [Laravel DDD Architect](https://marketplace.visualstudio.com/items?itemName=shahmy.laravel-ddd-architect) VSCode extension.

---

## Requirements

- PHP **8.1+**
- Laravel **10**, **11**, **12**, or **13**

---

## Installation

```bash
composer require shahmy/laravel-ddd-toolkit --dev
```

The service provider is auto-discovered. Publish the config file:

```bash
php artisan vendor:publish --tag=ddd-config
```

This creates `config/ddd.php` in your project.

---

## Configuration

`config/ddd.php`:

```php
return [
    // Root directory for all DDD domains (relative to base_path())
    'src_directory' => env('DDD_SRC_DIR', 'src'),

    // Base PSR-4 namespace mapped to src_directory
    'base_namespace' => 'Src',

    // Name of the cross-domain shared bounded context
    'shared_domain' => 'Shared',

    // Automatically add PSR-4 entry to composer.json after domain creation
    'auto_register_namespaces' => true,

    // Automatically run composer dump-autoload after namespace registration
    'auto_dump_autoload' => true,

    // PHP testing framework for generated test stubs
    'test_framework' => env('DDD_TEST_FRAMEWORK', 'pest'), // 'pest' or 'phpunit'

    // Layer structure created for every new domain
    'layers' => [
        'Application'                            => ['Actions', 'Commands', 'DTOs', 'Events', 'Exceptions', 'Interfaces', 'Jobs', 'Listeners', 'Queries', 'Services', 'UseCases'],
        'Domain'                                 => ['Entities', 'Enums', 'Events', 'Exceptions', 'Repositories', 'Services', 'Specifications', 'Traits', 'ValueObjects'],
        'Infrastructure'                         => ['Cache', 'Factories', 'Mappers', 'Providers', 'Services'],
        'Infrastructure/Persistence'             => ['Eloquent', 'Migrations', 'Repositories', 'Seeders'],
        'Presentation/API'                       => ['Controllers', 'Middleware', 'Requests', 'Resources', 'Routes'],
        'Presentation/CLI'                       => [],
        'Presentation/WEB'                       => [],
        'Tests'                                  => ['Feature', 'Unit'],
    ],
];
```

---

## Commands

### Project initialization

```bash
php artisan ddd:init
```

Creates the `src/` directory, scaffolds the `Shared` bounded context, and registers `Src\\` in `composer.json`.

---

### Domain scaffolding

```bash
php artisan ddd:domain Merchant
php artisan ddd:domain Merchant --dry-run    # preview without creating files
php artisan ddd:domain Merchant --force      # overwrite if already exists
```

Generates the full bounded context under `src/Merchant/` with all layers and subfolders. Every empty directory receives a `.gitkeep`. A `README.md` and `module.json` are created at the domain root.

---

### Class generators

All class generators follow the same pattern:

```bash
php artisan ddd:<type> <Domain> <ClassName> [--force] [--dry-run]
```

| Command | Output location | Generated class |
|---|---|---|
| `ddd:entity` | `Domain/Entities/` | Eloquent-backed domain entity |
| `ddd:repository` | `Domain/Repositories/` + `Infrastructure/Persistence/Repositories/` | Interface + Eloquent implementation |
| `ddd:service` | `Domain/Services/` | Domain service |
| `ddd:dto` | `Application/DTOs/` | `readonly` class with `fromArray`, `fromRequest`, `toArray` |
| `ddd:action` | `Application/Actions/` | Single-responsibility action |
| `ddd:usecase` | `Application/UseCases/` | Clean Architecture use case |
| `ddd:event` | `Domain/Events/` | Immutable domain event |
| `ddd:value-object` | `Domain/ValueObjects/` | `readonly` value object with invariant validation |
| `ddd:enum` | `Domain/Enums/` | Backed PHP 8.1+ enum |
| `ddd:policy` | `Domain/` | Laravel policy |
| `ddd:resource` | `Presentation/API/Resources/` | API resource |
| `ddd:request` | `Presentation/API/Requests/` | Form request |
| `ddd:test` | `Tests/Feature/` or `Tests/Unit/` | Pest or PHPUnit test |

**Examples:**

```bash
php artisan ddd:entity Merchant Product
php artisan ddd:repository Merchant ProductRepository
php artisan ddd:service Merchant PricingService
php artisan ddd:dto Merchant CreateProductDTO
php artisan ddd:action Merchant CreateProductAction
php artisan ddd:usecase Merchant CreateProductUseCase
php artisan ddd:event Merchant ProductCreated
php artisan ddd:value-object Merchant Money
php artisan ddd:enum Merchant ProductStatus
php artisan ddd:policy Merchant OrderPolicy
php artisan ddd:resource Merchant ProductResource
php artisan ddd:request Merchant StoreProductRequest
php artisan ddd:test Merchant ProductTest --unit
```

---

## Generated Domain Structure

```
src/Merchant/
├── Application/
│   ├── Actions/
│   ├── Commands/
│   ├── DTOs/
│   ├── Events/
│   ├── Exceptions/
│   ├── Interfaces/
│   ├── Jobs/
│   ├── Listeners/
│   ├── Queries/
│   ├── Services/
│   └── UseCases/
├── Domain/
│   ├── Entities/
│   ├── Enums/
│   ├── Events/
│   ├── Exceptions/
│   ├── Repositories/
│   ├── Services/
│   ├── Specifications/
│   ├── Traits/
│   └── ValueObjects/
├── Infrastructure/
│   ├── Cache/
│   ├── Factories/
│   ├── Mappers/
│   ├── Persistence/
│   │   ├── Eloquent/
│   │   ├── Migrations/
│   │   ├── Repositories/
│   │   └── Seeders/
│   ├── Providers/
│   └── Services/
├── Presentation/
│   ├── API/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   ├── Requests/
│   │   ├── Resources/
│   │   └── Routes/
│   ├── CLI/
│   └── WEB/
├── Tests/
│   ├── Feature/
│   └── Unit/
├── README.md
└── module.json
```

---

## Customising Stubs

Publish the built-in stubs to your project:

```bash
php artisan vendor:publish --tag=ddd-stubs
```

Stubs are copied to `stubs/ddd/` in your project root. Edit them freely. The toolkit will use your versions automatically — published stubs always take precedence over the package defaults.

Stub placeholders use `{{ PLACEHOLDER }}` syntax (case-insensitive):

| Placeholder | Value |
|---|---|
| `{{ NAMESPACE }}` | Full PSR-4 namespace for the class |
| `{{ CLASS }}` | Class name |
| `{{ DOMAIN }}` | Domain name |

---

## Package Architecture

```
src/
├── Commands/           # 15 Artisan commands (DddDomainCommand, DddEntityCommand, …)
├── Generators/
│   ├── DomainGenerator.php   # Atomic directory tree scaffolding
│   └── ClassGenerator.php    # Stub-based class file generation
├── Support/
│   ├── AtomicFilesystem.php  # Transactional filesystem (begin/stage/commit/rollback)
│   ├── ComposerUpdater.php   # PSR-4 namespace registration + dump-autoload
│   ├── StubEngine.php        # Stub resolution and placeholder replacement
│   └── NamespaceGenerator.php
└── DDDServiceProvider.php
```

**Atomic operations** — all file and directory creation runs inside a transaction. Any failure triggers a full rollback, leaving no partial structures on disk.

---

## Testing

```bash
composer install
./vendor/bin/pest
./vendor/bin/pest --coverage    # requires Xdebug or PCOV
```

Code style is enforced by [Laravel Pint](https://laravel.com/docs/pint):

```bash
./vendor/bin/pint               # fix
./vendor/bin/pint --test        # check only (CI mode)
```

---

## VSCode Integration

This package works as a standalone CLI toolkit. If you use VSCode, the [Laravel DDD Architect](https://marketplace.visualstudio.com/items?itemName=shahmy.laravel-ddd-architect) extension detects this package and delegates all generation commands to it — giving you the same results from the Command Palette or sidebar without typing Artisan commands manually.

---

## Contributing

1. Fork the repository and branch from `main`.
2. Use [Conventional Commits](https://www.conventionalcommits.org/) for commit messages.
3. Ensure `./vendor/bin/pest` passes and `./vendor/bin/pint --test` reports no violations.
4. Add a `CHANGELOG.md` entry under `[Unreleased]`.
5. Open a pull request.

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for release history.

---

## License

MIT © [shahmy](https://github.com/shahmy)
