<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;

interface ComposerJsonReaderInterface
{
    /**
     * @return array<string, mixed>
     * @throws RuntimeCommandException if he file is missing, unreadable, or contains invalid JSON.
     */
    public function read(string $directory): array;
}
