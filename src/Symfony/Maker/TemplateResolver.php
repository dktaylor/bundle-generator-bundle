<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Dktaylor\BundleGeneratorBundle\Symfony\Maker\TemplateResolverInterface;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;

class TemplateResolver implements TemplateResolverInterface
{
    private string $templatesDirectory;

    public function __construct(string $bundleRootDirectory)
    {
        $this->templatesDirectory = rtrim($bundleRootDirectory, '/') . '/templates';
    }

    public function getTemplatesDirectory(): string
    {
        return $this->templatesDirectory;
    }

    public function resolve(string $templateFile): string
    {
        $path = $this->templatesDirectory . '/' . ltrim($templateFile, '/');

        if (!file_exists($path) || !is_readable($path)) {
            throw new RuntimeCommandException(
                sprintf('Cannot read Template "%s". Ensure that the file exists and is readable', $path)
            );
        }

        return $path;
    }
}
