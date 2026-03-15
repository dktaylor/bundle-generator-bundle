<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Dktaylor\BundleGeneratorBundle\Symfony\Maker\ValueObject\GeneratorFactoryContext;
use Symfony\Bundle\MakerBundle\Generator;

interface GeneratorFactoryInterface
{
    public function create(GeneratorFactoryContext $context): Generator;
}
