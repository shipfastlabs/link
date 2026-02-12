<?php

declare(strict_types=1);

namespace ShipFastLabs\Link;

use InvalidArgumentException;

final readonly class PathHelper
{
    /**
     * @param  non-empty-string  $path
     */
    public function __construct(
        private string $path,
    ) {
    }

    public function isWildcard(): bool
    {
        return str_ends_with($this->path, DIRECTORY_SEPARATOR.'*');
    }

    /**
     * @return list<PathHelper>
     */
    public function getPathsFromWildcard(): array
    {
        /** @var list<string> $entries */
        $entries = glob($this->path, GLOB_ONLYDIR) ?: [];

        $helpers = [];

        /** @var non-empty-string $entry */
        foreach ($entries as $entry) {
            if (! file_exists($entry.DIRECTORY_SEPARATOR.'composer.json')) {
                continue;
            }

            $helpers[] = new self($entry);
        }

        return $helpers;
    }

    public function toAbsolutePath(string $workingDirectory): self
    {
        if (self::isAbsolutePath($this->path)) {
            return $this;
        }

        $path = $this->isWildcard()
            ? substr($this->path, 0, -2)
            : $this->path;

        $real = realpath($workingDirectory.DIRECTORY_SEPARATOR.$path);

        if ($real === false) {
            throw new InvalidArgumentException(
                sprintf('Cannot resolve absolute path to %s from %s.', $path, $workingDirectory)
            );
        }

        if ($this->isWildcard()) {
            $real .= DIRECTORY_SEPARATOR.'*';
        }

        return new self($real);
    }

    /**
     * @return non-empty-string
     */
    public function getNormalizedPath(): string
    {
        if (str_ends_with($this->path, DIRECTORY_SEPARATOR)) {
            /** @var non-empty-string $path */
            $path = substr($this->path, 0, -1);

            return $path;
        }

        return $this->path;
    }

    /**
     * @return non-empty-string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    public static function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || (strlen($path) > 1 && $path[1] === ':')
            || str_starts_with($path, '\\\\');
    }
}
