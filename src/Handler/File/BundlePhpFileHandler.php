<?php

namespace Dktaylor\BundleGeneratorBundle\Handler\File;

use Dktaylor\BundleGeneratorBundle\AnswerCollection;
use Dktaylor\BundleGeneratorBundle\Handler\FileGeneratorHandlerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

class BundlePhpFileHandler implements FileGeneratorHandlerInterface
{
    private const string TEMPLATE = 'files/bundle.php.twig';

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly Environment $twig,
    ) {}

    public function create($bundleDir, AnswerCollection $answers): void
    {
        $vendorName = $answers->get('vendorName');
        $bundleName = $answers->get('bundleName');

        $filename = $bundleDir.'/src/'.$vendorName.$bundleName.'.php';

        $contents = $this->twig->render(self::TEMPLATE, [
            'bundleDir' => $bundleDir,
            'bundleName' => $bundleName,
            'vendorName' => $vendorName,
            'extensionAlias' => $answers->get('extensionAlias'),
        ]);

        $this->filesystem->dumpFile($filename, $contents);
    }
}