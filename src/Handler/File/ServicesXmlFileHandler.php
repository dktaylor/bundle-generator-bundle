<?php

namespace Dktaylor\BundleGeneratorBundle\Handler\File;

use Dktaylor\BundleGeneratorBundle\AnswerCollection;
use Dktaylor\BundleGeneratorBundle\Handler\FileGeneratorHandlerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

class ServicesXmlFileHandler implements FileGeneratorHandlerInterface
{
    private const string TEMPLATE = 'files/services.xml.twig';

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly Environment $twig,
    ) {}

    public function create($bundleDir, ?AnswerCollection $answers = null): void
    {
        $contents = $this->twig->render('files/services.xml.twig');
        $this->filesystem->dumpFile($bundleDir.'/config/services.xml', $contents);
    }
}
