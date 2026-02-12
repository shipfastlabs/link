<?php

declare(strict_types=1);

use ShipFastLabs\Link\PathHelper;

it('detects wildcard paths', function (): void {
    $helper = new PathHelper('/some/path/*');
    expect($helper->isWildcard())->toBeTrue();
});

it('detects non-wildcard paths', function (): void {
    $helper = new PathHelper('/some/path/package');
    expect($helper->isWildcard())->toBeFalse();
});

it('normalizes trailing directory separator', function (): void {
    $helper = new PathHelper('/some/path/');
    expect($helper->getNormalizedPath())->toBe('/some/path');
});

it('returns path unchanged when no trailing separator', function (): void {
    $helper = new PathHelper('/some/path');
    expect($helper->getNormalizedPath())->toBe('/some/path');
});

it('detects absolute unix paths', function (): void {
    expect(PathHelper::isAbsolutePath('/some/path'))->toBeTrue();
});

it('detects absolute windows paths', function (): void {
    expect(PathHelper::isAbsolutePath('C:\\some\\path'))->toBeTrue();
});

it('detects absolute unc paths', function (): void {
    expect(PathHelper::isAbsolutePath('\\\\server\\share'))->toBeTrue();
});

it('detects relative paths', function (): void {
    expect(PathHelper::isAbsolutePath('../some/path'))->toBeFalse();
});

it('returns the raw path', function (): void {
    $helper = new PathHelper('/my/path');
    expect($helper->getPath())->toBe('/my/path');
});

it('expands wildcards to directories with composer.json', function (): void {
    $tempDir = sys_get_temp_dir().'/composer-link-test-'.uniqid();
    mkdir($tempDir.'/pkg-a', 0777, true);
    mkdir($tempDir.'/pkg-b', 0777, true);
    mkdir($tempDir.'/no-composer', 0777, true);

    file_put_contents($tempDir.'/pkg-a/composer.json', '{"name": "test/a"}');
    file_put_contents($tempDir.'/pkg-b/composer.json', '{"name": "test/b"}');

    $helper = new PathHelper($tempDir.'/*');
    $results = $helper->getPathsFromWildcard();

    $paths = array_map(fn (PathHelper $h): string => $h->getNormalizedPath(), $results);
    sort($paths);

    expect($paths)->toHaveCount(2)
        ->and($paths[0])->toContain('pkg-a')
        ->and($paths[1])->toContain('pkg-b');

    // Cleanup
    unlink($tempDir.'/pkg-a/composer.json');
    unlink($tempDir.'/pkg-b/composer.json');
    rmdir($tempDir.'/pkg-a');
    rmdir($tempDir.'/pkg-b');
    rmdir($tempDir.'/no-composer');
    rmdir($tempDir);
});

it('resolves relative path to absolute', function (): void {
    $tempDir = sys_get_temp_dir().'/composer-link-resolve-'.uniqid();
    mkdir($tempDir.'/sub', 0777, true);

    $helper = new PathHelper('sub');
    // Use realpath to normalize the temp dir (macOS resolves /var → /private/var)
    $absolute = $helper->toAbsolutePath((string) realpath($tempDir));

    expect($absolute->getNormalizedPath())->toBe(realpath($tempDir).'/sub');

    rmdir($tempDir.'/sub');
    rmdir($tempDir);
});

it('returns self for already absolute paths', function (): void {
    $helper = new PathHelper('/absolute/path');
    $result = $helper->toAbsolutePath('/some/working/dir');

    expect($result->getNormalizedPath())->toBe('/absolute/path');
});

it('throws on unresolvable relative paths', function (): void {
    $helper = new PathHelper('nonexistent-dir');
    $helper->toAbsolutePath('/some/working/dir');
})->throws(InvalidArgumentException::class);
