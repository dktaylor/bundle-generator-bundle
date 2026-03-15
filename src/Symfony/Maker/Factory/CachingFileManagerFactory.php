<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker\Factory;

use Dktaylor\BundleGeneratorBundle\Symfony\Maker\FileManagerFactoryInterface;
use Dktaylor\BundleGeneratorBundle\Symfony\Maker\ValueObject\FileManagerFactoryContext;
use Symfony\Bundle\MakerBundle\FileManager;

class CachingFileManagerFactory implements FileManagerFactoryInterface
{
    private array $cache = [];

    public function __construct(
        private readonly FileManagerFactoryInterface $inner,
    ) {}

    /**
     * Caching decorator for FileManagerFactoryInterface
     */
    public function create(FileManagerFactoryContext $context): FileManager
    {
        $key = hash('xxh3', $context->bundleDir . "\x00" . $context->templatePath);

        if (!isset($this->cache[$key])) {
            $this->cache[$key] = $this->inner->create($context);
        }

        return $this->cache[$key];
    }

    public function clearCache(): void
    {
        $this->cache = [];
    }
}
