<?php

declare(strict_types=1);

use ShipFastLabs\Link\LinkStorage;

beforeEach(function (): void {
    $this->tempDir = sys_get_temp_dir().'/composer-link-storage-'.uniqid();
    mkdir($this->tempDir, 0777, true);
    $this->storage = new LinkStorage($this->tempDir);
});

afterEach(function (): void {
    $file = $this->tempDir.'/composer-link.json';
    if (file_exists($file)) {
        unlink($file);
    }
    rmdir($this->tempDir);
});

it('returns empty list when no packages linked', function (): void {
    expect($this->storage->all())->toBe([]);
});

it('stores and retrieves a linked package', function (): void {
    $this->storage->add('vendor/package', '/path/to/pkg', '^1.0', false, 'require');

    $all = $this->storage->all();
    expect($all)->toHaveCount(1)
        ->and($all[0]['name'])->toBe('vendor/package')
        ->and($all[0]['path'])->toBe('/path/to/pkg')
        ->and($all[0]['originalConstraint'])->toBe('^1.0')
        ->and($all[0]['wasNewRequirement'])->toBeFalse()
        ->and($all[0]['requireSection'])->toBe('require');
});

it('finds package by path', function (): void {
    $this->storage->add('vendor/package', '/path/to/pkg', '^1.0', false, 'require');

    $found = $this->storage->findByPath('/path/to/pkg');
    expect($found)->not->toBeNull()
        ->and($found['name'])->toBe('vendor/package');
});

it('finds package by name', function (): void {
    $this->storage->add('vendor/package', '/path/to/pkg', '^1.0', false, 'require');

    $found = $this->storage->findByName('vendor/package');
    expect($found)->not->toBeNull()
        ->and($found['path'])->toBe('/path/to/pkg');
});

it('returns null when package not found by path', function (): void {
    expect($this->storage->findByPath('/nonexistent'))->toBeNull();
});

it('returns null when package not found by name', function (): void {
    expect($this->storage->findByName('nonexistent/pkg'))->toBeNull();
});

it('removes a package by name', function (): void {
    $this->storage->add('vendor/package', '/path/to/pkg', '^1.0', false, 'require');
    $this->storage->remove('vendor/package');

    expect($this->storage->all())->toBe([]);
});

it('overwrites existing package with same name', function (): void {
    $this->storage->add('vendor/package', '/old/path', '^1.0', false, 'require');
    $this->storage->add('vendor/package', '/new/path', '^2.0', false, 'require');

    $all = $this->storage->all();
    expect($all)->toHaveCount(1)
        ->and($all[0]['path'])->toBe('/new/path')
        ->and($all[0]['originalConstraint'])->toBe('^2.0');
});

it('handles new requirements with null constraint', function (): void {
    $this->storage->add('vendor/new-pkg', '/path/to/new', null, true, 'require');

    $found = $this->storage->findByName('vendor/new-pkg');
    expect($found['originalConstraint'])->toBeNull()
        ->and($found['wasNewRequirement'])->toBeTrue();
});

it('handles require-dev packages', function (): void {
    $this->storage->add('vendor/dev-pkg', '/path/to/dev', '^1.0', false, 'require-dev');

    $found = $this->storage->findByName('vendor/dev-pkg');
    expect($found['requireSection'])->toBe('require-dev');
});
