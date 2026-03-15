<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;

interface ProcessRunnerInterface
{
    /**
     * @param list<string> $cmd
     * @throws RuntimeCommandException on non-zero exit
     */
    public function run(array $cmd, ConsoleStyle $io, string $failureMessage): void;
}
