<?php

declare(strict_types=1);

namespace ShipFastLabs\Link\Commands\Concerns;

use ShipFastLabs\Link\ComposerJsonManipulator;
use ShipFastLabs\Link\LinkManager;
use ShipFastLabs\Link\LinkStorage;
use ShipFastLabs\Link\PathHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

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
    private function runComposerUpdate(array $packageNames, OutputInterface $output): int
    {
        /** @var \Composer\Console\Application $application */
        $application = $this->getApplication();

        return $application->run(new ArrayInput([
            'command' => 'update',
            'packages' => $packageNames,
            '--no-interaction' => true,
        ]), $output);
    }
}
