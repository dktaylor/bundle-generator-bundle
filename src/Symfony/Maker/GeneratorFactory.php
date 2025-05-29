<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Util\TemplateComponentGenerator;

class GeneratorFactory
{
    public function __construct(
        private FileManagerFactory $fileManagerFactory,
        private ?TemplateComponentGenerator $templateComponentGenerator = null,
    ) {}

    public function create(string $namespacePrefix, string $bundleDir): Generator
    {
        $fileManager = $this->fileManagerFactory->create($bundleDir);

        return new Generator(
            $fileManager,
            $namespacePrefix,
            null,
            $this->templateComponentGenerator,
        );
    }
}
