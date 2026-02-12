<?php

declare(strict_types=1);

use Composer\IO\BufferIO;
use ShipFastLabs\Link\ComposerJsonManipulator;
use ShipFastLabs\Link\LinkManager;
use ShipFastLabs\Link\LinkStorage;

beforeEach(function (): void {
    $this->tempDir = sys_get_temp_dir().'/composer-link-manager-'.uniqid();
    mkdir($this->tempDir, 0777, true);

    $this->composerJsonPath = $this->tempDir.'/composer.json';
    file_put_contents($this->composerJsonPath, json_encode([
        'name' => 'test/project',
        'require' => [
            'php' => '^8.1',
            'vendor/existing' => '^1.0',
        ],
        'require-dev' => [
            'vendor/dev-pkg' => '^2.0',
        ],
    ], JSON_PRETTY_PRINT));

    $this->vendorDir = $this->tempDir.'/vendor';
    mkdir($this->vendorDir, 0777, true);

    $this->packageDir = $this->tempDir.'/packages/my-package';
    mkdir($this->packageDir, 0777, true);
    file_put_contents($this->packageDir.'/composer.json', json_encode([
        'name' => 'vendor/existing',
    ], JSON_PRETTY_PRINT));

    $this->newPackageDir = $this->tempDir.'/packages/new-package';
    mkdir($this->newPackageDir, 0777, true);
    file_put_contents($this->newPackageDir.'/composer.json', json_encode([
        'name' => 'vendor/new-pkg',
    ], JSON_PRETTY_PRINT));

    $this->manipulator = new ComposerJsonManipulator($this->composerJsonPath);
    $this->storage = new LinkStorage($this->vendorDir);
    $this->io = new BufferIO();
    $this->manager = new LinkManager($this->manipulator, $this->storage, $this->io);
});

afterEach(function (): void {
    $cleanup = function (string $dir) use (&$cleanup): void {
        foreach (glob($dir.'/*') as $file) {
            is_dir($file) ? $cleanup($file) : unlink($file);
        }
        rmdir($dir);
    };
    $cleanup($this->tempDir);
});

it('reads package name from composer.json', function (): void {
    expect($this->manager->getPackageName($this->packageDir))->toBe('vendor/existing');
});

it('throws when no composer.json in path', function (): void {
    $emptyDir = $this->tempDir.'/empty';
    mkdir($emptyDir);

    $this->manager->getPackageName($emptyDir);
})->throws(RuntimeException::class, 'No composer.json found');

it('throws when composer.json has no name', function (): void {
    $noNameDir = $this->tempDir.'/no-name';
    mkdir($noNameDir);
    file_put_contents($noNameDir.'/composer.json', json_encode(['description' => 'no name']));

    $this->manager->getPackageName($noNameDir);
})->throws(RuntimeException::class, 'No valid "name" field');

it('links an existing required package', function (): void {
    $name = $this->manager->link($this->packageDir);

    expect($name)->toBe('vendor/existing');

    // Verify composer.json was modified
    $data = json_decode(file_get_contents($this->composerJsonPath), true);
    expect($data['require']['vendor/existing'])->toBe('*');
    expect($data['repositories'])->toBeArray();

    // Verify tracking
    $tracked = $this->storage->findByName('vendor/existing');
    expect($tracked)->not->toBeNull()
        ->and($tracked['originalConstraint'])->toBe('^1.0')
        ->and($tracked['wasNewRequirement'])->toBeFalse()
        ->and($tracked['requireSection'])->toBe('require');
});

it('links a new package', function (): void {
    $name = $this->manager->link($this->newPackageDir);

    expect($name)->toBe('vendor/new-pkg');

    $data = json_decode(file_get_contents($this->composerJsonPath), true);
    expect($data['require']['vendor/new-pkg'])->toBe('*');

    $tracked = $this->storage->findByName('vendor/new-pkg');
    expect($tracked['wasNewRequirement'])->toBeTrue()
        ->and($tracked['originalConstraint'])->toBeNull();
});

it('skips already linked packages', function (): void {
    $this->manager->link($this->packageDir);
    $result = $this->manager->link($this->packageDir);

    expect($result)->toBeNull();
    expect($this->io->getOutput())->toContain('already linked');
});

it('unlinks a package and restores original constraint', function (): void {
    $this->manager->link($this->packageDir);
    $name = $this->manager->unlink($this->packageDir);

    expect($name)->toBe('vendor/existing');

    $data = json_decode(file_get_contents($this->composerJsonPath), true);
    expect($data['require']['vendor/existing'])->toBe('^1.0');

    expect($this->storage->findByName('vendor/existing'))->toBeNull();
});

it('unlinks a new package by removing require entirely', function (): void {
    $this->manager->link($this->newPackageDir);
    $this->manager->unlink($this->newPackageDir);

    $data = json_decode(file_get_contents($this->composerJsonPath), true);
    expect($data['require'])->not->toHaveKey('vendor/new-pkg');
});

it('returns null when unlinking non-tracked path', function (): void {
    $result = $this->manager->unlink('/nonexistent/path');

    expect($result)->toBeNull();
    expect($this->io->getOutput())->toContain('No linked package found');
});

it('unlinks all packages', function (): void {
    $this->manager->link($this->packageDir);
    $this->manager->link($this->newPackageDir);

    $unlinked = $this->manager->unlinkAll();

    expect($unlinked)->toHaveCount(2)
        ->and($this->storage->all())->toBe([]);

    $data = json_decode(file_get_contents($this->composerJsonPath), true);
    expect($data['require']['vendor/existing'])->toBe('^1.0')
        ->and($data['require'])->not->toHaveKey('vendor/new-pkg');
});

it('returns linked packages list', function (): void {
    $this->manager->link($this->packageDir);

    $packages = $this->manager->getLinkedPackages();
    expect($packages)->toHaveCount(1)
        ->and($packages[0]['name'])->toBe('vendor/existing');
});

it('checks if package is installed via composer.lock', function (): void {
    $lockPath = $this->tempDir.'/composer.lock';
    file_put_contents($lockPath, json_encode([
        'packages' => [
            ['name' => 'vendor/installed'],
        ],
        'packages-dev' => [
            ['name' => 'vendor/dev-installed'],
        ],
    ]));

    expect($this->manager->isPackageInstalled('vendor/installed', $lockPath))->toBeTrue()
        ->and($this->manager->isPackageInstalled('vendor/dev-installed', $lockPath))->toBeTrue()
        ->and($this->manager->isPackageInstalled('vendor/not-installed', $lockPath))->toBeFalse();
});

it('returns false for non-existent lock file', function (): void {
    expect($this->manager->isPackageInstalled('vendor/pkg', '/nonexistent/composer.lock'))->toBeFalse();
});
