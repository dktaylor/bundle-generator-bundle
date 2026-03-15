<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;

interface TemplateResolverInterface
{
    /**
     * Returns the absolute path to the templates' directory.
     */
    public function getTemplatesDirectory(): string;

    /**
     * Resolves and validates a template file path within the templates' directory.
     *
     * @throws RuntimeCommandException if the template does not exist or is not readable.
     */
    public function resolve(string $templateFile): string;
}
