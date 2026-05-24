<?php

declare(strict_types=1);

use Shahmy\LaravelDddToolkit\Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Pest Configuration
|--------------------------------------------------------------------------
|
| Apply the custom TestCase (which extends Orchestra\Testbench\TestCase,
| registers DDDServiceProvider, and sets sensible defaults) to all tests.
|
*/

uses(TestCase::class)->in('Feature', 'Unit');
