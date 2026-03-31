<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker\Adapter;

use Composer\Autoload\ClassLoader;
use Dktaylor\BundleGeneratorBundle\Symfony\Maker\ClassLoaderFinderInterface;
use Symfony\Bundle\MakerBundle\Util\ComposerAutoloaderFinder;

class ComposerAutoloaderFinderAdapter implements ClassLoaderFinderInterface
{
    public function __construct(
        private readonly ComposerAutoloaderFinder $inner,
    ) {}
    public function getClassLoader(): ClassLoader
    {
        return $this->inner->getClassLoader();
    }
}
