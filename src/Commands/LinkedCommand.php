<?php

declare(strict_types=1);

namespace ShipFastLabs\Link\Commands;

use Composer\Command\BaseCommand;
use ShipFastLabs\Link\LinkStorage;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class LinkedCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('linked');
        $this->setDescription('List all linked packages');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $composer = $this->requireComposer();
        /** @var string $vendorDir */
        $vendorDir = $composer->getConfig()->get('vendor-dir');

        $storage = new LinkStorage($vendorDir);
        $packages = $storage->all();

        if ($packages === []) {
            $output->writeln('<comment>No linked packages.</comment>');

            return 0;
        }

        $table = new Table($output);
        $table->setHeaders(['Package', 'Path', 'Original Constraint']);

        foreach ($packages as $package) {
            $constraint = match (true) {
                $package['wasNewRequirement'] => '(new)',
                $package['originalConstraint'] !== null => $package['originalConstraint'],
                default => '-',
            };

            $table->addRow([
                $package['name'],
                $package['path'],
                $constraint,
            ]);
        }

        $table->render();

        return 0;
    }
}
