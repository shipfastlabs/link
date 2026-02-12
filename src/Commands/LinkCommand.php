<?php

declare(strict_types=1);

namespace ShipFastLabs\Link\Commands;

use Composer\Command\BaseCommand;
use ShipFastLabs\Link\Commands\Concerns\ResolvesComposerDependencies;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class LinkCommand extends BaseCommand
{
    use ResolvesComposerDependencies;

    protected function configure(): void
    {
        $this->setName('link');
        $this->setDescription('Link a local package for development');
        $this->addArgument('path', InputArgument::REQUIRED, 'Path to the package directory (supports wildcards)');
        $this->addOption(
            'only-installed',
            null,
            InputOption::VALUE_NONE,
            'When using wildcards, only link packages already in composer.lock',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $composer = $this->requireComposer();
        $io = $this->getIO();
        $manager = $this->createLinkManager();

        $composerJsonPath = $composer->getConfig()->getConfigSource()->getName();
        $composerLockPath = dirname($composerJsonPath).DIRECTORY_SEPARATOR.'composer.lock';

        /** @var non-empty-string $pathArgument */
        $pathArgument = $input->getArgument('path');
        $onlyInstalled = (bool) $input->getOption('only-installed');

        $linkedPackageNames = [];

        foreach ($this->resolvePaths($pathArgument) as $path) {
            $normalizedPath = $path->getNormalizedPath();

            if ($onlyInstalled) {
                $packageName = $manager->getPackageName($normalizedPath);
                if (! $manager->isPackageInstalled($packageName, $composerLockPath)) {
                    $io->write(sprintf('<comment>Skipping %s (not installed)</comment>', $packageName));

                    continue;
                }
            }

            $name = $manager->link($normalizedPath);
            if ($name !== null) {
                $linkedPackageNames[] = $name;
            }
        }

        if ($linkedPackageNames === []) {
            $io->write('<comment>No packages were linked.</comment>');

            return 0;
        }

        return $this->runComposerUpdate($linkedPackageNames);
    }
}
