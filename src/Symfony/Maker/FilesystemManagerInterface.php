<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;

interface FilesystemManagerInterface
{
    /**
     * Creates a lib/ symlink for the bundle, rolling back the bundle directory on failure.
     *
     * @throws RuntimeCommandException if the symlink cannot be created
     */
    public function createLibSymlink(string $bundleDir, string $rootDirectory, string $bundleFullName, BundleIOInterface $io): void;
}
