<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker\ValueObject;

readonly class FileManagerFactoryContext
{
    public function __construct(
        public string $bundleDir,
        public string $templatePath
    ) {}
}