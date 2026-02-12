<?php

declare(strict_types=1);

namespace ShipFastLabs\Link\Commands;

use Composer\Command\BaseCommand;
use ShipFastLabs\Link\Commands\Concerns\ResolvesComposerDependencies;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class UnlinkCommand extends BaseCommand
{
    use ResolvesComposerDependencies;

    protected function configure(): void
    {
        $this->setName('unlink');
        $this->setDescription('Unlink a linked package');
        $this->addArgument('path', InputArgument::REQUIRED, 'Path to the package directory (supports wildcards)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getIO();
        $manager = $this->createLinkManager();

        /** @var non-empty-string $pathArgument */
        $pathArgument = $input->getArgument('path');

        $unlinkedPackageNames = [];

        foreach ($this->resolvePaths($pathArgument) as $path) {
            $name = $manager->unlink($path->getNormalizedPath());
            if ($name !== null) {
                $unlinkedPackageNames[] = $name;
            }
        }

        if ($unlinkedPackageNames === []) {
            $io->write('<comment>No packages were unlinked.</comment>');

            return 0;
        }

        return $this->runComposerUpdate($unlinkedPackageNames);
    }
}
