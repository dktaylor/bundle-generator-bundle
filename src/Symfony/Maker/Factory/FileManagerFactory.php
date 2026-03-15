<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker\Factory;

use Dktaylor\BundleGeneratorBundle\Symfony\Maker\FileManagerFactoryInterface;
use Dktaylor\BundleGeneratorBundle\Symfony\Maker\ValueObject\FileManagerFactoryContext;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Util\AutoloaderUtil;
use Symfony\Bundle\MakerBundle\Util\MakerFileLinkFormatter;
use Symfony\Component\Filesystem\Filesystem;

class FileManagerFactory implements FileManagerFactoryInterface
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly AutoloaderUtil $autoloaderUtil,
        private readonly MakerFileLinkFormatter $makerFileLinkFormatter,
    ) {}

    /**
     * Creates a FileManager scoped to a given root directory and template path.
     *
     * @throws \InvalidArgumentException when the twig path is not a valid directory path.
     */
    public function create(FileManagerFactoryContext $context): FileManager
    {
        if (!$this->filesystem->exists($context->templatePath)
            || !is_dir($context->templatePath)
        ) {
            throw new \InvalidArgumentException(sprintf('The twig path "%s" does not exist', $context->templatePath));
        }

        return new FileManager(
            $this->filesystem,
            $this->autoloaderUtil,
            $this->makerFileLinkFormatter,
            $context->bundleDir,
            $context->templatePath,
        );
    }
}
