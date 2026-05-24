<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | DDD Source Directory
    |--------------------------------------------------------------------------
    |
    | The root directory for all DDD bounded contexts, relative to the Laravel
    | project root. By default, this is 'src/' which gives you src/Merchant/,
    | src/Order/, etc.
    |
    */
    'src_directory' => 'src',

    /*
    |--------------------------------------------------------------------------
    | Base PSR-4 Namespace
    |--------------------------------------------------------------------------
    |
    | The root PSR-4 namespace registered in composer.json's autoload.psr-4.
    | For example, if 'src_directory' is 'src' and 'base_namespace' is 'Src',
    | your autoload entry will be: "Src\\": "src/"
    |
    */
    'base_namespace' => 'Src',

    /*
    |--------------------------------------------------------------------------
    | Shared Domain Name
    |--------------------------------------------------------------------------
    |
    | The name of the cross-cutting "Shared" bounded context. This domain is
    | always scaffolded first during `ddd:init` and contains base classes,
    | traits, contracts, and other shared infrastructure.
    |
    */
    'shared_domain' => 'Shared',

    /*
    |--------------------------------------------------------------------------
    | Test Framework
    |--------------------------------------------------------------------------
    |
    | The PHP test framework to use when generating test stubs.
    | Supported: 'pest', 'phpunit'
    |
    */
    'test_framework' => env('DDD_TEST_FRAMEWORK', 'pest'),

    /*
    |--------------------------------------------------------------------------
    | Custom Stubs Path
    |--------------------------------------------------------------------------
    |
    | If you have published the stubs (via `php artisan vendor:publish --tag=ddd-stubs`)
    | and customised them, set this to the directory containing your custom stubs.
    | Set to null to use the package's built-in stubs.
    |
    | You can also publish stubs without setting this — the package auto-detects
    | a 'stubs/ddd/' directory in your project root.
    |
    */
    'stubs_path' => null,

    /*
    |--------------------------------------------------------------------------
    | Layer Structure
    |--------------------------------------------------------------------------
    |
    | Defines the directory structure created for each domain.
    | Keys are layers; values are the immediate subdirectories inside that layer.
    | Use '/' to create nested paths (e.g. 'Infrastructure/Persistence').
    |
    */
    'layers' => [
        'Application' => [
            'Actions',
            'Commands',
            'DTOs',
            'Events',
            'Exceptions',
            'Interfaces',
            'Jobs',
            'Listeners',
            'Queries',
            'Services',
            'UseCases',
        ],
        'Domain' => [
            'Entities',
            'Enums',
            'Events',
            'Exceptions',
            'Repositories',
            'Services',
            'Specifications',
            'Traits',
            'ValueObjects',
        ],
        'Infrastructure' => [
            'Cache',
            'Factories',
            'Mappers',
            'Providers',
            'Services',
        ],
        'Infrastructure/Persistence' => [
            'Eloquent',
            'Migrations',
            'Repositories',
            'Seeders',
        ],
        'Presentation/API' => [
            'Controllers',
            'Middleware',
            'Requests',
            'Resources',
            'Routes',
        ],
        'Presentation/CLI' => [],
        'Presentation/WEB' => [],
        'Tests' => [
            'Feature',
            'Unit',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Register Namespaces
    |--------------------------------------------------------------------------
    |
    | When true, the toolkit automatically registers new domain namespaces
    | in composer.json after scaffolding. Set to false if you manage
    | composer.json manually.
    |
    */
    'auto_register_namespaces' => true,

    /*
    |--------------------------------------------------------------------------
    | Auto Dump Autoload
    |--------------------------------------------------------------------------
    |
    | When true, `composer dump-autoload` is executed automatically after
    | namespace registration. Requires Composer to be available in PATH.
    |
    */
    'auto_dump_autoload' => true,

];
