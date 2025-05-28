<?php

namespace Dktaylor\BundleGeneratorBundle\Handler\Directory;

use Dktaylor\BundleGeneratorBundle\Handler\DirectoryGeneratorHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Filesystem\Filesystem;

#[AsTaggedItem]
class AssetsDirectoryHandler implements DirectoryGeneratorHandlerInterface
{
    public function __construct(
        private readonly Filesystem $filesystem,
    ) {}

    public function create($bundleDir): void
    {
        $this->filesystem->mkdir($bundleDir .'/assets');
    }
}
