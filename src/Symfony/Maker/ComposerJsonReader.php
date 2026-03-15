<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Dktaylor\BundleGeneratorBundle\Symfony\Maker\ComposerJsonReaderInterface;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;

class ComposerJsonReader implements ComposerJsonReaderInterface
{
    private array $composerJsonCache = [];

    public function read(string $directory): array
    {
        if (isset($this->composerJsonCache[$directory])) {
            return $this->composerJsonCache[$directory];
        }

        $file = $this->ensureFileExistsAndIsReadable($directory. '/composer.json');
        $contents = $this->retrieveContents($file);

        try {
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new RuntimeCommandException(
                sprintf('composer.json in "%s" contains invalid JSON: %s', $directory, $e->getMessage()),
                previous: $e
            );
        }

        if (!is_array($data)) {
            throw new RuntimeCommandException(
                sprintf('composer.json in "%s" is valid JSON but does not contain an object.', $directory)
            );
        }

        return $this->composerJsonCache[$directory] = $data;
    }

    private function ensureFileExistsAndIsReadable(string $filename): string
    {
        $file = realpath($filename);
        if (false === $file || !file_exists($file) || !is_readable($file)) {
            throw new RuntimeCommandException(sprintf('Cannot read "%s". Ensure that the file exists and is readable.', $filename));
        }

        return $file;
    }

    private function retrieveContents(string $file): string
    {
        $contents = file_get_contents($file);
        if (false === $contents) {
            throw new RuntimeCommandException(
                sprintf('Failed to read "%s".', $file)
            );
        }

        return $contents;
    }
}
