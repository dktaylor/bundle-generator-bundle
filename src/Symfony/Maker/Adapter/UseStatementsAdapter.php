<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker\Adapter;

use Dktaylor\BundleGeneratorBundle\Symfony\Maker\UseStatementsInterface;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;

class UseStatementsAdapter implements UseStatementsInterface
{
    private readonly UseStatementGenerator $inner;

    public function __construct(array $classes)
    {
        $this->inner = new UseStatementGenerator($classes);
    }

    /**
     * @inheritDoc
     */
    public function unwrap(): mixed
    {
        return $this->inner;
    }
}
