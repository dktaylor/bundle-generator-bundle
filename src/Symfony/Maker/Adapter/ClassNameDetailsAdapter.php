<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker\Adapter;

use Dktaylor\BundleGeneratorBundle\Symfony\Maker\ClassNameDetailsInterface;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;

class ClassNameDetailsAdapter implements ClassNameDetailsInterface
{
    public function __construct(
        private readonly ClassNameDetails $inner,
    ) {}

    public function getFullName(): string
    {
        return $this->inner->getFullName();
    }
}
