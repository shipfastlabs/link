<?php

declare(strict_types=1);

namespace ShipFastLabs\Link;

use Composer\Config\JsonConfigSource;
use Composer\Json\JsonFile;

final readonly class ComposerJsonManipulator
{
    private JsonFile $jsonFile;

    private JsonConfigSource $configSource;

    public function __construct(string $composerJsonPath)
    {
        $this->jsonFile = new JsonFile($composerJsonPath);
        $this->configSource = new JsonConfigSource($this->jsonFile);
    }

    public function addPathRepository(string $path): void
    {
        $this->configSource->addRepository($this->repoName($path), [
            'type' => 'path',
            'url' => $path,
            'canonical' => false,
            'options' => ['symlink' => true],
        ]);
    }

    public function removePathRepository(string $path): void
    {
        $this->configSource->removeRepository($this->repoName($path));
    }

    public function addLink(string $section, string $packageName, string $constraint): void
    {
        $this->configSource->addLink($section, $packageName, $constraint);
    }

    public function removeLink(string $section, string $packageName): void
    {
        $this->configSource->removeLink($section, $packageName);
    }

    public function getRequireConstraint(string $packageName): ?string
    {
        /** @var array{require?: array<string, string>, require-dev?: array<string, string>} $data */
        $data = $this->jsonFile->read();

        return $data['require'][$packageName] ?? $data['require-dev'][$packageName] ?? null;
    }

    public function isDevRequirement(string $packageName): bool
    {
        /** @var array{require-dev?: array<string, string>} $data */
        $data = $this->jsonFile->read();

        return isset($data['require-dev'][$packageName]);
    }

    public function getRequireSection(string $packageName): string
    {
        return $this->isDevRequirement($packageName) ? 'require-dev' : 'require';
    }

    private function repoName(string $path): string
    {
        return 'shipfastlabs-link-'.md5($path);
    }
}
