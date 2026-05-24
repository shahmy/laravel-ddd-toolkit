<?php

declare(strict_types=1);

use Shahmy\LaravelDddToolkit\Support\NamespaceGenerator;

beforeEach(function (): void {
    $this->gen = new NamespaceGenerator('Src', 'src');
});

describe('NamespaceGenerator', function (): void {

    it('generates a domain namespace', function (): void {
        expect($this->gen->forDomain('Merchant'))->toBe('Src\\Merchant');
    });

    it('generates a layer namespace', function (): void {
        expect($this->gen->forLayer('Merchant', 'Domain'))->toBe('Src\\Merchant\\Domain');
    });

    it('generates a class namespace with subfolder', function (): void {
        expect($this->gen->forClass('Merchant', 'Domain', 'Entities'))
            ->toBe('Src\\Merchant\\Domain\\Entities');
    });

    it('filters empty parts from forClass', function (): void {
        expect($this->gen->forClass('Merchant', 'Domain', ''))
            ->toBe('Src\\Merchant\\Domain');
    });

    it('converts a relative path to namespace', function (): void {
        expect($this->gen->pathToNamespace('Merchant/Domain/Entities'))
            ->toBe('Src\\Merchant\\Domain\\Entities');
    });

    it('converts a namespace back to path', function (): void {
        expect($this->gen->namespaceToPath('Src\\Merchant\\Domain\\Entities'))
            ->toBe('src/Merchant/Domain/Entities');
    });

    it('returns the correct src autoload entry', function (): void {
        $entry = $this->gen->getSrcAutoloadEntry();
        expect($entry['namespace'])->toBe('Src\\');
        expect($entry['path'])->toBe('src/');
    });

    it('respects a custom base namespace', function (): void {
        $gen = new NamespaceGenerator('App\\Domains', 'src/domains');
        expect($gen->forDomain('Merchant'))->toBe('App\\Domains\\Merchant');
    });

});
