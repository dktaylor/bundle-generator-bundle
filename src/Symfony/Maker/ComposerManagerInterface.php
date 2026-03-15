<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Dktaylor\BundleGeneratorBundle\Symfony\Maker\ValueObject\ScaffoldContext;
use Symfony\Bundle\MakerBundle\ConsoleStyle;

interface ComposerManagerInterface
{
    public function init(ScaffoldContext $context, ConsoleStyle $io): void;
    public function hasLibRepo(string $rootDirectory): bool;
    public function addLibRepo(string $rootDirectory, ConsoleStyle $io): void;
}
