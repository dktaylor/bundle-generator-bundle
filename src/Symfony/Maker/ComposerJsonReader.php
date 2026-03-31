<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Component\Filesystem\Filesystem;

class ComposerJsonReader implements ComposerJsonReaderInterface
{
    private array $cache = [];

    public function __construct(
        private readonly Filesystem $filesystem,
    ) {}

    /**
     * Returns array<string, mixed>
     * @throws RuntimeCommandException if the file is missing, unreadable, or contains invalid JSON.
     */
    public function read(string $directory): array
    {
        if (isset($this->cache[$directory])) {
            return $this->cache[$directory];
        }

        $path = rtrim($directory, '/') . '/composer.json';

        if (!$this->filesystem->exists($path) || !is_readable($path)) {
            throw new RuntimeCommandException(
                sprintf('Cannot read "%s". Ensure that the file exists and is readable.', $path)
            );
        }

        $contents = file_get_contents($path);
        if (false === $contents) {
            throw new RuntimeCommandException(sprintf('Failed to read "%s".', $path));
        }

        try {
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new RuntimeCommandException(
                sprintf('composer.json in "%s" contains invalid JSON: %s', $directory, $e->getMessage()),
                previous: $e
            );
        }

        if (!is_array($data) || array_is_list($data)) {
            throw new RuntimeCommandException(
                sprintf('composer.json in "%s" is valid JSON but does not contain an object.', $directory)
            );
        }

        return $this->cache[$directory] = $data;
    }
}
