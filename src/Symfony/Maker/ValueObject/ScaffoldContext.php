<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker\ValueObject;

use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Console\Input\InputInterface;

class ScaffoldContext
{
    public function __construct(
        public readonly string $bundleFullName,
        public readonly string $namespacePrefix,
        public readonly string $packageName,
        public readonly string $bundleDir,
        public readonly string $rootDirectory,
        public readonly string $templatePath,
        public readonly string $extensionAlias,
        public readonly bool $initComposer,
        public readonly string $authorName,
        public readonly string $bundleDescription,
        public readonly string $bundleClassTemplatePath,
        public readonly array $fileManifest,
        public readonly object $useStatements,
    ) {}

    public static function fromInput(
        InputInterface $input,
        Generator $generator,
        TemplateResolverInterface $templateResolver,
    ): self {
        $bundleFullName = $input->getArgument('bundle');
        $words = self::splitBundleNameWords($bundleFullName);

        $namespacePrefix = trim($words[0].'\\'.$words[1].$words[2], '\\');
        $packageName = strtolower(trim($words[0].'/'.$words[1].'_'.$words[2], '/\\'));
        $bundleShortName = trim($words[1].$words[2], '\\');
        $bundleDir = '';

        return new self(
            bundleFullName: $bundleFullName,
            namespacePrefix: $namespacePrefix,
            packageName: $packageName,
            bundleDir: $bundleDir,
            rootDirectory: $generator->getRootDirectory(),
            templatePath: $templateResolver->getTemplateDirectory(),
            extensionAlias: $input->getOption('extension-alias'),
            initComposer: $input->getOption('do-init-composer'),
            authorName: $input->getOption('author-name') ?? '',
            bundleDescription: $input->getOption('bundle-description') ?? '',
            bundleClassTemplatePath: $templateResolver->resolve('bundle/Bundle.tpl.php'),
            fileManifest: self::buildFileManifest($bundleDir, $words[0], $bundleShortName, $templateResolver),
            useStatements: self::buildUseStatements(),
        );
    }
}
