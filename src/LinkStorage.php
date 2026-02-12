<?php

declare(strict_types=1);

namespace ShipFastLabs\Link;

final readonly class LinkStorage
{
    private string $filePath;

    public function __construct(string $vendorDir)
    {
        $this->filePath = $vendorDir.DIRECTORY_SEPARATOR.'composer-link.json';
    }

    public function add(
        string $name,
        string $path,
        ?string $originalConstraint,
        bool $wasNewRequirement,
        string $requireSection,
    ): void {
        $data = $this->read();

        $data['packages'] = array_values(array_filter(
            $data['packages'],
            static fn (array $p): bool => $p['name'] !== $name
        ));

        $data['packages'][] = [
            'name' => $name,
            'path' => $path,
            'originalConstraint' => $originalConstraint,
            'wasNewRequirement' => $wasNewRequirement,
            'requireSection' => $requireSection,
        ];

        $this->write($data);
    }

    public function remove(string $name): void
    {
        $data = $this->read();

        $data['packages'] = array_values(array_filter(
            $data['packages'],
            static fn (array $p): bool => $p['name'] !== $name
        ));

        $this->write($data);
    }

    /**
     * @return array{name: string, path: string, originalConstraint: ?string, wasNewRequirement: bool, requireSection: string}|null
     */
    public function findByPath(string $path): ?array
    {
        foreach ($this->read()['packages'] as $package) {
            if ($package['path'] === $path) {
                return $package;
            }
        }

        return null;
    }

    /**
     * @return array{name: string, path: string, originalConstraint: ?string, wasNewRequirement: bool, requireSection: string}|null
     */
    public function findByName(string $name): ?array
    {
        foreach ($this->read()['packages'] as $package) {
            if ($package['name'] === $name) {
                return $package;
            }
        }

        return null;
    }

    /**
     * @return list<array{name: string, path: string, originalConstraint: ?string, wasNewRequirement: bool, requireSection: string}>
     */
    public function all(): array
    {
        return $this->read()['packages'];
    }

    /**
     * @return array{packages: list<array{name: string, path: string, originalConstraint: ?string, wasNewRequirement: bool, requireSection: string}>}
     */
    private function read(): array
    {
        if (! file_exists($this->filePath)) {
            return ['packages' => []];
        }

        $contents = file_get_contents($this->filePath);

        if ($contents === false) {
            return ['packages' => []];
        }

        /** @var array{packages: list<array{name: string, path: string, originalConstraint: ?string, wasNewRequirement: bool, requireSection: string}>}|null $decoded */
        $decoded = json_decode($contents, true);

        return $decoded ?? ['packages' => []];
    }

    /**
     * @param  array{packages: list<array{name: string, path: string, originalConstraint: ?string, wasNewRequirement: bool, requireSection: string}>}  $data
     */
    private function write(array $data): void
    {
        file_put_contents(
            $this->filePath,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
        );
    }
}
