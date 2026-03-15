<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker\Factory;

use Dktaylor\BundleGeneratorBundle\Symfony\Maker\FileManagerFactoryInterface;
use Dktaylor\BundleGeneratorBundle\Symfony\Maker\GeneratorFactoryInterface;
use Dktaylor\BundleGeneratorBundle\Symfony\Maker\ValueObject\FileManagerFactoryContext;
use Dktaylor\BundleGeneratorBundle\Symfony\Maker\ValueObject\GeneratorFactoryContext;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Util\PhpCompatUtil;
use Symfony\Bundle\MakerBundle\Util\TemplateComponentGenerator;

readonly class GeneratorFactory implements GeneratorFactoryInterface
{
    public function __construct(
        private FileManagerFactoryInterface $fileManagerFactory,
        private TemplateComponentGenerator $templateComponentGenerator,
        private PhpCompatUtil $phpCompatUtil,
    ) {}

    /**
     * Creates a scoped Generator for a given bundle directory.
     */
    public function create(GeneratorFactoryContext $context): Generator
    {
        return new Generator(
            $this->fileManagerFactory->create($this->buildFileManagerContext($context)),
            $context->namespacePrefix,
            $this->phpCompatUtil,
            $this->templateComponentGenerator,
        );
    }

    private function buildFileManagerContext(GeneratorFactoryContext $context): FileManagerFactoryContext
    {
        return new FileManagerFactoryContext(
            $context->bundleDir,
            $context->templatePath
        );
    }
}
