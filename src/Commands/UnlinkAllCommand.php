<?php

declare(strict_types=1);

namespace ShipFastLabs\Link\Commands;

use Composer\Command\BaseCommand;
use ShipFastLabs\Link\Commands\Concerns\ResolvesComposerDependencies;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class UnlinkAllCommand extends BaseCommand
{
    use ResolvesComposerDependencies;

    protected function configure(): void
    {
        $this->setName('unlink-all');
        $this->setDescription('Unlink all linked packages');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getIO();
        $manager = $this->createLinkManager();

        $unlinkedNames = $manager->unlinkAll();

        if ($unlinkedNames === []) {
            $io->write('<comment>No linked packages found.</comment>');

            return 0;
        }

        return $this->runComposerUpdate($unlinkedNames);
    }
}
