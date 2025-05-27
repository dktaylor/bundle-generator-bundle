<?php

namespace Dktaylor\BundleGeneratorBundle\Handler\File;

use Dktaylor\BundleGeneratorBundle\AnswerCollection;
use Dktaylor\BundleGeneratorBundle\Handler\FileGeneratorHandlerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

class DocsIndexFileHandler implements FileGeneratorHandlerInterface
{
    public function __construct(
        private readonly Filesystem $filesystem
    ) {}

    public function create($bundleDir, AnswerCollection $answers): void
    {
        $this->filesystem->dumpFile($bundleDir.'/docs/index.rst', "");
    }
}
