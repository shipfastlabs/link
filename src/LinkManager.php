<?php

declare(strict_types=1);

namespace ShipFastLabs\Link;

use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use RuntimeException;

final readonly class LinkManager
{
    public function __construct(
        private ComposerJsonManipulator $manipulator,
        private LinkStorage $storage,
        private IOInterface $io,
    ) {
    }

    /**
     * Read the package name from the target directory's composer.json.
     *
     * @param  non-empty-string  $path
     */
    public function getPackageName(string $path): string
    {
        $composerJsonPath = $path.DIRECTORY_SEPARATOR.'composer.json';

        if (! file_exists($composerJsonPath)) {
            throw new RuntimeException(
                sprintf('No composer.json found at %s', $composerJsonPath)
            );
        }

        $jsonFile = new JsonFile($composerJsonPath);
        /** @var array<string, mixed> $data */
        $data = $jsonFile->read();

        if (! isset($data['name']) || ! is_string($data['name'])) {
            throw new RuntimeException(
                sprintf('No valid "name" field found in %s', $composerJsonPath)
            );
        }

        return $data['name'];
    }

    /**
     * Link a single package from a local path.
     *
     * @param  non-empty-string  $path  The path as provided by the user (relative or absolute)
     * @return string|null The package name if linked, null if skipped
     */
    public function link(string $path): ?string
    {
        $packageName = $this->getPackageName($path);

        $existing = $this->storage->findByName($packageName);
        if ($existing !== null) {
            $this->io->write(sprintf(
                '<comment>Package %s is already linked from %s</comment>',
                $packageName,
                $existing['path']
            ));

            return null;
        }

        $currentConstraint = $this->manipulator->getRequireConstraint($packageName);
        $wasNewRequirement = $currentConstraint === null;
        $requireSection = $wasNewRequirement ? 'require' : $this->manipulator->getRequireSection($packageName);

        $this->storage->add(
            $packageName,
            $path,
            $currentConstraint,
            $wasNewRequirement,
            $requireSection,
        );

        $this->manipulator->addPathRepository($path);
        $this->manipulator->addLink($requireSection, $packageName, '*');

        $this->io->write(sprintf(
            '<info>Linked %s from %s</info>',
            $packageName,
            $path
        ));

        return $packageName;
    }

    /**
     * Unlink a single package by its tracked path.
     *
     * @param  non-empty-string  $path
     * @return string|null The package name if unlinked, null if not found
     */
    public function unlink(string $path): ?string
    {
        $tracked = $this->storage->findByPath($path);

        if ($tracked === null) {
            $this->io->write(sprintf(
                '<comment>No linked package found at %s</comment>',
                $path
            ));

            return null;
        }

        $this->restorePackage($tracked);

        $this->io->write(sprintf(
            '<info>Unlinked %s</info>',
            $tracked['name']
        ));

        return $tracked['name'];
    }

    /**
     * Unlink all tracked packages.
     *
     * @return list<string> Package names that were unlinked
     */
    public function unlinkAll(): array
    {
        $unlinkedNames = [];

        foreach ($this->storage->all() as $tracked) {
            $this->restorePackage($tracked);
            $unlinkedNames[] = $tracked['name'];

            $this->io->write(sprintf(
                '<info>Unlinked %s</info>',
                $tracked['name']
            ));
        }

        return $unlinkedNames;
    }

    /**
     * @return list<array{name: string, path: string, originalConstraint: ?string, wasNewRequirement: bool, requireSection: string}>
     */
    public function getLinkedPackages(): array
    {
        return $this->storage->all();
    }

    /**
     * Check if a package name is currently in composer.lock.
     */
    public function isPackageInstalled(string $packageName, string $composerLockPath): bool
    {
        if (! file_exists($composerLockPath)) {
            return false;
        }

        $lockFile = new JsonFile($composerLockPath);
        /** @var array{packages?: list<array{name: string}>, packages-dev?: list<array{name: string}>} $lockData */
        $lockData = $lockFile->read();

        foreach ([...$lockData['packages'] ?? [], ...$lockData['packages-dev'] ?? []] as $pkg) {
            if ($pkg['name'] === $packageName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Restore a tracked package to its original state in composer.json.
     *
     * @param  array{name: string, path: string, originalConstraint: ?string, wasNewRequirement: bool, requireSection: string}  $tracked
     */
    private function restorePackage(array $tracked): void
    {
        $this->manipulator->removePathRepository($tracked['path']);

        if ($tracked['wasNewRequirement']) {
            $this->manipulator->removeLink($tracked['requireSection'], $tracked['name']);
        } else {
            $this->manipulator->addLink(
                $tracked['requireSection'],
                $tracked['name'],
                (string) $tracked['originalConstraint'],
            );
        }

        $this->storage->remove($tracked['name']);
    }
}
