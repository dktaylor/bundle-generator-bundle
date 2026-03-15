<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Dktaylor\BundleGeneratorBundle\Symfony\Maker\ValueObject\ScaffoldContext;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Component\Console\Input\InputInterface;

interface ComposerManagerInterface
{
    public function init(ScaffoldContext $context, ConsoleStyle $io): void;
    public function hasLibRepo(string $rootDirectory): bool;
    public function addLibRepo(string $rootDirectory, ConsoleStyle $io): void;

    public function runComposerInit(string $bundleDir, string $packageName, InputInterface $input, ConsoleStyle $io): void;

    public function runComposerAddLibRepo(string $rootDirectory, ConsoleStyle $io): void;
}
