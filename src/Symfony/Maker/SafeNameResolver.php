<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;

readonly class SafeNameResolver implements SafeNameResolverInterface
{
    public function resolve(string $name): string
    {
        if ('' === $name) {
            throw new RuntimeCommandException('Bundle name cannot be empty.');
        }

        // *nix allows backslash as a valid filename character.
        if (str_contains($name, '\\')) {
            throw new RuntimeCommandException(
                sprintf('Bundle name "%s" is invalid or contains illegal path characters.', $name)
            );
        }

        $safeName = basename($name);

        if ($safeName !== $name) {
            throw new RuntimeCommandException(
                sprintf('Bundle name "%s" is invalid or contains illegal path characters.', $name)
            );
        }

        return $safeName;
    }
}
