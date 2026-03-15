<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker\ValueObject;

readonly class GeneratorFactoryContext
{
    public function __construct(
        public string $namespacePrefix,
        public string $bundleDir,
        public string $templatePath,
    ) {}
}
