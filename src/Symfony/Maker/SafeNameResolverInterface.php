<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;

interface SafeNameResolverInterface
{
    /**
     * Returns the safe basename of a name, rejecting anything containing path separators.
     *
     * @throws RuntimeCommandException if the name contains illegal path characters.
     */
    public function resolve(string $name): string;
}
