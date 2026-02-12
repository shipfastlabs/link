<?php

declare(strict_types=1);

namespace ShipFastLabs\Link;

use Composer\Command\BaseCommand;
use Composer\Plugin\Capability\CommandProvider as ComposerCommandProvider;
use ShipFastLabs\Link\Commands\LinkCommand;
use ShipFastLabs\Link\Commands\LinkedCommand;
use ShipFastLabs\Link\Commands\UnlinkAllCommand;
use ShipFastLabs\Link\Commands\UnlinkCommand;

final class CommandProvider implements ComposerCommandProvider
{
    /**
     * @param  array<string, mixed>  $arguments  Required by Composer's CommandProvider capability.
     *
     * @phpstan-ignore constructor.unusedParameter
     */
    public function __construct(array $arguments = [])
    {
    }

    /**
     * @return list<BaseCommand>
     */
    public function getCommands(): array
    {
        return [
            new LinkCommand(),
            new UnlinkCommand(),
            new UnlinkAllCommand(),
            new LinkedCommand(),
        ];
    }
}
