<?php

namespace Dktaylor\BundleGeneratorBundle\Handler;

use Dktaylor\BundleGeneratorBundle\AnswerCollection;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('dktaylor_bundle_generator.file_generator_handler')]
interface FileGeneratorHandlerInterface
{
    public function create($bundleDir, AnswerCollection $answers): void;
}