<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Dktaylor\BundleGeneratorBundle\Symfony\Maker\ValueObject\ScaffoldContext;

interface ComposerManagerInterface
{
    public function init(ScaffoldContext $context, BundleIOInterface $io): void;
    public function hasLibRepo(string $rootDirectory): bool;
    public function addLibRepo(string $rootDirectory, BundleIOInterface $io): void;
}
