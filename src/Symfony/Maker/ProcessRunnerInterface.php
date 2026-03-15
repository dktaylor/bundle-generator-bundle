<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;

interface ProcessRunnerInterface
{
    /** @param list<string> $cmd */
    public function run(array $cmd, ConsoleStyle $io, string $failureMessage): void;
}
