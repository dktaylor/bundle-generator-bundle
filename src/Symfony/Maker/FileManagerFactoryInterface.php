<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Dktaylor\BundleGeneratorBundle\Symfony\Maker\ValueObject\FileManagerFactoryContext;
use Symfony\Bundle\MakerBundle\FileManager;

interface FileManagerFactoryInterface
{
    public function create(FileManagerFactoryContext $context): FileManager;
}
