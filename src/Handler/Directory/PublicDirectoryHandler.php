<?php

namespace Dktaylor\BundleGeneratorBundle\Handler\Directory;

use Dktaylor\BundleGeneratorBundle\Handler\DirectoryGeneratorHandlerInterface;
use Symfony\Component\Filesystem\Filesystem;

class PublicDirectoryHandler implements DirectoryGeneratorHandlerInterface
{
    public function __construct(
        private readonly Filesystem $filesystem,
    ) {}

    public function create($bundleDir): void
    {
        $this->filesystem->mkdir($bundleDir .'/public');
    }
}
