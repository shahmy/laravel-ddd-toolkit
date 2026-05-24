<?php

declare(strict_types=1);

namespace Shahmy\LaravelDddToolkit\Tests;

use Shahmy\LaravelDddToolkit\DDDServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Register the DDD service provider for every test.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [DDDServiceProvider::class];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('ddd.src_directory', 'src');
        $app['config']->set('ddd.base_namespace', 'Src');
        $app['config']->set('ddd.shared_domain', 'Shared');
        $app['config']->set('ddd.auto_register_namespaces', false);
        $app['config']->set('ddd.auto_dump_autoload', false);
    }
}
