<?php

declare(strict_types=1);

namespace ShipFastLabs\Link\Commands\Concerns;

use Composer\DependencyResolver\Request;
use Composer\Factory;
use Composer\Installer;
use ShipFastLabs\Link\ComposerJsonManipulator;
use ShipFastLabs\Link\LinkManager;
use ShipFastLabs\Link\LinkStorage;
use ShipFastLabs\Link\PathHelper;

trait ResolvesComposerDependencies
{
    private function createLinkManager(): LinkManager
    {
        $composer = $this->requireComposer();
        $composerJsonPath = $composer->getConfig()->getConfigSource()->getName();
        /** @var string $vendorDir */
        $vendorDir = $composer->getConfig()->get('vendor-dir');

        return new LinkManager(
            new ComposerJsonManipulator($composerJsonPath),
            new LinkStorage($vendorDir),
            $this->getIO(),
        );
    }

    /**
     * @param  non-empty-string  $pathArgument
     * @return list<PathHelper>
     */
    private function resolvePaths(string $pathArgument): array
    {
        $pathHelper = new PathHelper($pathArgument);

        if ($pathHelper->isWildcard()) {
            return $pathHelper->getPathsFromWildcard();
        }

        return [$pathHelper];
    }

    /**
     * @param  list<string>  $packageNames
     */
    private function runComposerUpdate(array $packageNames): int
    {
        $io = $this->getIO();

        // Create a fresh Composer instance that reads the modified composer.json
        $composer = (new Factory())->createComposer($io);

        $installer = Installer::create($io, $composer);

        /** @phpstan-ignore method.deprecated */
        $installer
            ->setUpdate(true)
            ->setDevMode(true)
            ->setUpdateAllowList($packageNames)
            ->setUpdateAllowTransitiveDependencies(Request::UPDATE_LISTED_WITH_TRANSITIVE_DEPS);

        return $installer->run();
    }
}
