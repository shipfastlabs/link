<?php

declare(strict_types=1);

use ShipFastLabs\Link\ComposerJsonManipulator;

beforeEach(function (): void {
    $this->tempDir = sys_get_temp_dir().'/composer-link-manipulator-'.uniqid();
    mkdir($this->tempDir, 0777, true);
    $this->composerJsonPath = $this->tempDir.'/composer.json';
});

afterEach(function (): void {
    if (file_exists($this->composerJsonPath)) {
        unlink($this->composerJsonPath);
    }
    rmdir($this->tempDir);
});

it('adds a path repository to composer.json', function (): void {
    file_put_contents($this->composerJsonPath, json_encode([
        'name' => 'test/project',
        'require' => ['php' => '^8.1'],
    ], JSON_PRETTY_PRINT));

    $manipulator = new ComposerJsonManipulator($this->composerJsonPath);
    $manipulator->addPathRepository('../../packages/my-package');

    $data = json_decode(file_get_contents($this->composerJsonPath), true);

    expect($data['repositories'])->toBeArray()
        ->and($data['repositories'])->toHaveCount(1);

    $repo = reset($data['repositories']);
    expect($repo['type'])->toBe('path')
        ->and($repo['url'])->toBe('../../packages/my-package')
        ->and($repo['options']['symlink'])->toBeTrue();
});

it('removes a path repository from composer.json', function (): void {
    file_put_contents($this->composerJsonPath, json_encode([
        'name' => 'test/project',
        'require' => ['php' => '^8.1'],
    ], JSON_PRETTY_PRINT));

    $manipulator = new ComposerJsonManipulator($this->composerJsonPath);
    $manipulator->addPathRepository('../../packages/my-package');
    $manipulator->removePathRepository('../../packages/my-package');

    $data = json_decode(file_get_contents($this->composerJsonPath), true);

    expect($data['repositories'] ?? [])->toBeEmpty();
});

it('adds a require entry', function (): void {
    file_put_contents($this->composerJsonPath, json_encode([
        'name' => 'test/project',
        'require' => ['php' => '^8.1'],
    ], JSON_PRETTY_PRINT));

    $manipulator = new ComposerJsonManipulator($this->composerJsonPath);
    $manipulator->addLink('require', 'vendor/package', '*');

    $data = json_decode(file_get_contents($this->composerJsonPath), true);

    expect($data['require']['vendor/package'])->toBe('*');
});

it('adds a require-dev entry', function (): void {
    file_put_contents($this->composerJsonPath, json_encode([
        'name' => 'test/project',
        'require' => ['php' => '^8.1'],
    ], JSON_PRETTY_PRINT));

    $manipulator = new ComposerJsonManipulator($this->composerJsonPath);
    $manipulator->addLink('require-dev', 'vendor/dev-package', '^1.0');

    $data = json_decode(file_get_contents($this->composerJsonPath), true);

    expect($data['require-dev']['vendor/dev-package'])->toBe('^1.0');
});

it('removes a require entry', function (): void {
    file_put_contents($this->composerJsonPath, json_encode([
        'name' => 'test/project',
        'require' => [
            'php' => '^8.1',
            'vendor/package' => '^1.0',
        ],
    ], JSON_PRETTY_PRINT));

    $manipulator = new ComposerJsonManipulator($this->composerJsonPath);
    $manipulator->removeLink('require', 'vendor/package');

    $data = json_decode(file_get_contents($this->composerJsonPath), true);

    expect($data['require'])->not->toHaveKey('vendor/package');
});

it('gets require constraint from require section', function (): void {
    file_put_contents($this->composerJsonPath, json_encode([
        'name' => 'test/project',
        'require' => [
            'php' => '^8.1',
            'vendor/package' => '^2.0',
        ],
    ], JSON_PRETTY_PRINT));

    $manipulator = new ComposerJsonManipulator($this->composerJsonPath);

    expect($manipulator->getRequireConstraint('vendor/package'))->toBe('^2.0');
});

it('gets require constraint from require-dev section', function (): void {
    file_put_contents($this->composerJsonPath, json_encode([
        'name' => 'test/project',
        'require' => ['php' => '^8.1'],
        'require-dev' => ['vendor/dev-pkg' => '^3.0'],
    ], JSON_PRETTY_PRINT));

    $manipulator = new ComposerJsonManipulator($this->composerJsonPath);

    expect($manipulator->getRequireConstraint('vendor/dev-pkg'))->toBe('^3.0');
});

it('returns null for non-existent package constraint', function (): void {
    file_put_contents($this->composerJsonPath, json_encode([
        'name' => 'test/project',
        'require' => ['php' => '^8.1'],
    ], JSON_PRETTY_PRINT));

    $manipulator = new ComposerJsonManipulator($this->composerJsonPath);

    expect($manipulator->getRequireConstraint('nonexistent/pkg'))->toBeNull();
});

it('detects dev requirements', function (): void {
    file_put_contents($this->composerJsonPath, json_encode([
        'name' => 'test/project',
        'require' => ['vendor/prod' => '^1.0'],
        'require-dev' => ['vendor/dev' => '^2.0'],
    ], JSON_PRETTY_PRINT));

    $manipulator = new ComposerJsonManipulator($this->composerJsonPath);

    expect($manipulator->isDevRequirement('vendor/dev'))->toBeTrue()
        ->and($manipulator->isDevRequirement('vendor/prod'))->toBeFalse();
});

it('returns correct require section', function (): void {
    file_put_contents($this->composerJsonPath, json_encode([
        'name' => 'test/project',
        'require' => ['vendor/prod' => '^1.0'],
        'require-dev' => ['vendor/dev' => '^2.0'],
    ], JSON_PRETTY_PRINT));

    $manipulator = new ComposerJsonManipulator($this->composerJsonPath);

    expect($manipulator->getRequireSection('vendor/prod'))->toBe('require')
        ->and($manipulator->getRequireSection('vendor/dev'))->toBe('require-dev');
});
