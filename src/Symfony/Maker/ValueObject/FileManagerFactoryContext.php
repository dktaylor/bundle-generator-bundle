<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker\ValueObject;

final readonly class FileManagerFactoryContext
{
    public function __construct(
        public string $bundleDir,
        public string $templatePath
    ) {}
}