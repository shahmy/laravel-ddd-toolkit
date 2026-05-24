<?php

declare(strict_types=1);

use Shahmy\LaravelDddToolkit\Support\AtomicFilesystem;
use Shahmy\LaravelDddToolkit\Support\StubEngine;
use Illuminate\Filesystem\Filesystem;

beforeEach(function (): void {
    $this->testRoot  = sys_get_temp_dir() . '/ddd-stub-test-' . uniqid('', true);
    $this->stubsDir  = $this->testRoot . '/stubs';
    $this->outputDir = $this->testRoot . '/output';

    $files = new Filesystem();
    $files->makeDirectory($this->stubsDir,  0755, true);
    $files->makeDirectory($this->outputDir, 0755, true);

    // Create a test stub
    file_put_contents(
        $this->stubsDir . '/test-class.stub',
        "<?php\n\nnamespace {{ NAMESPACE }};\n\nfinal class {{ CLASS_NAME }} {}\n",
    );

    $this->engine = new StubEngine($this->stubsDir, new AtomicFilesystem());
});

afterEach(function (): void {
    (new Filesystem())->deleteDirectory($this->testRoot);
});

describe('StubEngine', function (): void {

    describe('render', function (): void {
        it('replaces {{ }} placeholders', function (): void {
            $result = $this->engine->render('test-class', [
                'NAMESPACE'  => 'Src\\Merchant\\Domain\\Entities',
                'CLASS_NAME' => 'Product',
            ]);

            expect($result)->toContain('namespace Src\\Merchant\\Domain\\Entities;');
            expect($result)->toContain('final class Product {}');
        });

        it('is case-insensitive for placeholder keys', function (): void {
            file_put_contents($this->stubsDir . '/case-test.stub', '{{ namespace }} / {{ NAMESPACE }}');

            $result = $this->engine->render('case-test', ['NAMESPACE' => 'Src\\Test']);

            expect($result)->toContain('Src\\Test');
        });

        it('throws RuntimeException for missing stubs', function (): void {
            expect(fn () => $this->engine->render('non-existent', []))->toThrow(\RuntimeException::class);
        });

        it('handles multiple occurrences of the same placeholder', function (): void {
            file_put_contents($this->stubsDir . '/multi.stub', '{{ CLASS_NAME }} extends Base{{ CLASS_NAME }}');

            $result = $this->engine->render('multi', ['CLASS_NAME' => 'Product']);

            expect($result)->toBe('Product extends BaseProduct');
        });
    });

    describe('renderToFile', function (): void {
        it('creates a file at the target path', function (): void {
            $target = $this->outputDir . '/Product.php';

            $this->engine->renderToFile('test-class', [
                'NAMESPACE'  => 'Src\\Merchant\\Domain\\Entities',
                'CLASS_NAME' => 'Product',
            ], $target);

            expect(file_exists($target))->toBeTrue();
            expect(file_get_contents($target))->toContain('final class Product {}');
        });

        it('throws if file exists and force=false', function (): void {
            $target = $this->outputDir . '/Existing.php';
            file_put_contents($target, '<?php // existing');

            expect(fn () => $this->engine->renderToFile('test-class', [], $target, false))
                ->toThrow(\RuntimeException::class, 'already exists');
        });

        it('overwrites when force=true', function (): void {
            $target = $this->outputDir . '/Overwrite.php';
            file_put_contents($target, '<?php // old content');

            $this->engine->renderToFile('test-class', [
                'NAMESPACE'  => 'Src\\Test',
                'CLASS_NAME' => 'Overwrite',
            ], $target, true);

            expect(file_get_contents($target))->toContain('final class Overwrite {}');
        });
    });

    describe('getAvailableStubs', function (): void {
        it('lists all .stub files without extension', function (): void {
            $stubs = $this->engine->getAvailableStubs();

            expect($stubs)->toContain('test-class');
        });
    });

    describe('stubExists', function (): void {
        it('returns true for existing stubs', function (): void {
            expect($this->engine->stubExists('test-class'))->toBeTrue();
        });

        it('returns false for missing stubs', function (): void {
            expect($this->engine->stubExists('does-not-exist'))->toBeFalse();
        });
    });

});
