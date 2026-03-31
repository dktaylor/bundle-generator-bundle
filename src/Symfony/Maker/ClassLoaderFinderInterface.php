<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Composer\Autoload\ClassLoader;

interface ClassLoaderFinderInterface
{
    public function getClassLoader(): ClassLoader;
}
