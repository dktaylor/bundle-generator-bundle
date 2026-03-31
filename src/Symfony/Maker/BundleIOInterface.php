<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

interface BundleIOInterface
{
    public function success(string $message): void;
    public function writeln(string|iterable $messages, int $options = 0): void;
    public function confirm(string $question, bool $default = true): bool;
}
