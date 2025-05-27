<?php

namespace Dktaylor\BundleGeneratorBundle\Handler;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('dktaylor_bundle_generator.directory_generator_handler')]
interface DirectoryGeneratorHandlerInterface
{
    public function create($bundleDir): void;
}
