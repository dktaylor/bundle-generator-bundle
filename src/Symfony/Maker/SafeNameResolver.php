<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;

readonly class SafeNameResolver implements SafeNameResolverInterface
{
    public function resolve(string $name): string
    {
        $safeName = basename($name);

        if ($safeName !== $name) {
            throw new RuntimeCommandException(
                sprintf('Bundle name "%s" is invalid or contains illegal path characters.', $bundleFullName)
            );
        }

        return $safeName;
    }
}
