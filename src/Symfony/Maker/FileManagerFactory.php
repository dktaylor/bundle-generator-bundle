<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Util\AutoloaderUtil;

use Symfony\Bundle\MakerBundle\Util\MakerFileLinkFormatter;
use Symfony\Component\Filesystem\Filesystem;

class FileManagerFactory
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly AutoloaderUtil $autoloaderUtil,
        private readonly MakerFileLinkFormatter $makerFileLinkFormatter,
    ) {}

    public function create(string $bundleDir): FileManager
    {
        return new FileManager(
            $this->filesystem,
            $this->autoloaderUtil,
            $this->makerFileLinkFormatter,
            $bundleDir,
            realpath(__DIR__ . '/../../../templates'),
        );
    }
}
