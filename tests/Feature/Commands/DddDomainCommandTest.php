<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;

// TestCase (with DDDServiceProvider registered) is applied via Pest.php

beforeEach(function (): void {
    $this->files    = new Filesystem();
    $this->testRoot = sys_get_temp_dir() . '/ddd-cmd-test-' . uniqid('', true);
    $this->files->makeDirectory($this->testRoot . '/src', 0755, true);

    config([
        // Use the absolute temp path so DomainGenerator::resolveSrcPath() writes here
        // without needing to override base_path() (which is not easily overridable in Testbench).
        'ddd.src_directory'           => $this->testRoot . '/src',
        'ddd.base_namespace'          => 'Src',
        'ddd.shared_domain'           => 'Shared',
        'ddd.auto_register_namespaces' => false,
        'ddd.auto_dump_autoload'       => false,
        'ddd.layers'                  => [
            'Application' => ['Actions', 'DTOs', 'UseCases'],
            'Domain'      => ['Entities', 'Repositories', 'ValueObjects'],
            'Infrastructure' => ['Persistence'],
            'Presentation/API' => ['Controllers'],
            'Tests'       => ['Feature', 'Unit'],
        ],
    ]);
});

afterEach(function (): void {
    $this->files->deleteDirectory($this->testRoot);
});

describe('ddd:domain command', function (): void {

    it('creates the full domain structure', function (): void {
        $this->artisan('ddd:domain', ['name' => 'Merchant'])
            ->assertSuccessful()
            ->expectsOutputToContain('Domain')
            ->expectsOutputToContain('Merchant');

        expect(is_dir($this->testRoot . '/src/Merchant'))->toBeTrue();
        expect(is_dir($this->testRoot . '/src/Merchant/Application'))->toBeTrue();
        expect(is_dir($this->testRoot . '/src/Merchant/Domain'))->toBeTrue();
        expect(is_dir($this->testRoot . '/src/Merchant/Infrastructure'))->toBeTrue();
        expect(is_dir($this->testRoot . '/src/Merchant/Tests'))->toBeTrue();
    });

    it('creates README.md inside the domain', function (): void {
        $this->artisan('ddd:domain', ['name' => 'Order'])->assertSuccessful();

        expect(file_exists($this->testRoot . '/src/Order/README.md'))->toBeTrue();
    });

    it('creates module.json inside the domain', function (): void {
        $this->artisan('ddd:domain', ['name' => 'Order'])->assertSuccessful();

        $moduleJsonPath = $this->testRoot . '/src/Order/module.json';
        expect(file_exists($moduleJsonPath))->toBeTrue();

        $moduleJson = json_decode(file_get_contents($moduleJsonPath), true);
        expect($moduleJson['name'])->toBe('Order');
        expect($moduleJson['namespace'])->toBe('Src\\Order');
    });

    it('rejects lowercase domain names', function (): void {
        $this->artisan('ddd:domain', ['name' => 'merchant'])
            ->assertFailed()
            ->expectsOutputToContain('PascalCase');
    });

    it('rejects names with spaces', function (): void {
        $this->artisan('ddd:domain', ['name' => 'My Domain'])
            ->assertFailed();
    });

    it('refuses to overwrite without --force', function (): void {
        $this->artisan('ddd:domain', ['name' => 'Merchant'])->assertSuccessful();
        $this->artisan('ddd:domain', ['name' => 'Merchant'])
            ->assertFailed()
            ->expectsOutputToContain('already exists');
    });

    it('overwrites with --force', function (): void {
        $this->artisan('ddd:domain', ['name' => 'Merchant'])->assertSuccessful();
        $this->artisan('ddd:domain', ['name' => 'Merchant', '--force' => true])->assertSuccessful();
    });

    it('previews without creating files in --dry-run mode', function (): void {
        $this->artisan('ddd:domain', ['name' => 'Preview', '--dry-run' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('[dry-run]');

        expect(is_dir($this->testRoot . '/src/Preview'))->toBeFalse();
    });

    it('adds .gitkeep to empty directories', function (): void {
        $this->artisan('ddd:domain', ['name' => 'Merchant'])->assertSuccessful();

        // Application/Actions is empty — should have .gitkeep
        $actionsDir = $this->testRoot . '/src/Merchant/Application/Actions';
        expect(is_dir($actionsDir))->toBeTrue();
        expect(file_exists($actionsDir . '/.gitkeep'))->toBeTrue();
    });

});
