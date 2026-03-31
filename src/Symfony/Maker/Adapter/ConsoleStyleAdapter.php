<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker\Adapter;

use Dktaylor\BundleGeneratorBundle\Symfony\Maker\BundleIOInterface;
use Symfony\Bundle\MakerBundle\ConsoleStyle;

class ConsoleStyleAdapter implements BundleIOInterface
{
    public function __construct(
        private readonly ConsoleStyle $inner,
    ) {}

    public function success(string $message): void
    {
        $this->inner->success($message);
    }

    public function writeln(iterable|string $messages, int $options = 0): void
    {
        $this->inner->writeln($messages, $options);
    }

    public function confirm(string $question, bool $default = true): bool
    {
        return $this->inner->confirm($question, $default);
    }
}
