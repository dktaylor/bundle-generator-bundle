<?php

namespace Dktaylor\BundleGeneratorBundle;

use Symfony\Component\HttpKernel\KernelInterface;

class KernelUtility
{
    private ?string $libDir = null;

    public function __construct(
        private readonly KernelInterface $kernel,
    ) {}

    public function getProjectDir(): string
    {
        return $this->kernel->getProjectDir();
    }

    public function getLibDir(): string
    {
        return $this->kernel->getProjectDir().'/lib/';
    }

    public function getBundleDir(string $bundleName): string
    {
        return $this->libDir.'/'.$bundleName;
    }

    public function getBundleProjectDir(): string
    {
        return $this->kernel->getProjectDir() . '/../';
    }
}