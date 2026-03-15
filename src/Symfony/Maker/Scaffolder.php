<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Dktaylor\BundleGeneratorBundle\Symfony\Maker\ValueObject\GeneratorFactoryContext;
use Dktaylor\BundleGeneratorBundle\Symfony\Maker\ValueObject\ScaffoldContext;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Generator;

class Scaffolder
{
    public function __construct(
        private readonly GeneratorFactoryInterface $generatorFactory,
        private readonly ComposerManagerInterface $composerManager,
        private readonly FilesystemManagerInterface $filesystemManager,
    ) {}

    public function scaffold(ScaffoldContext $context, ConsoleStyle $io): void
    {
        $bundleGenerator = $this->bundleGenerator($context);

        $this->registerPsr4Namespace($bundleGenerator);
        $this->generateBundleClass($bundleGenerator, $context);
        $this->generateBundleFiles($bundleGenerator, $context);
        $bundleGenerator->writeChanges();

        if ($context->initComposer) {
            $this->composerManager->init($context, $io);
        }

        $this->filesystemManager->createLibSymlink($context->bundleDir, $context->rootDirectory, $io);

        if (!$this->composerManager->hasLibRepo($context->rootDirectory)) {
            $this->composerManager->addLibRepo($context->rootDirectory, $io);
        }
    }

    private function buildBundleGenerator(BundleScaffoldContext $context): Generator
    {
        return $this->generatorFactory->create(
            new GeneratorFactoryContext(
                $context->namespacePrefix,
                $context->bundleDir,
                $context->templatePath,
            )
        );
    }

    private function registerPsr4Namespace(Generator $bundleGenerator): void
    {
        $classLoader = $this->composerAutoloaderFinder->getClassLoader();
        $classLoader->addPsr4($bundleGenerator->getRootNamespace().'\\', $bundleGenerator->getRootDirectory().'/src/');
    }

    private function generateBundleClass(Generator $bundleGenerator, ScaffoldContext $context): void
    {
        $classNameDetails = $bundleGenerator->createClassNameDetails(
            $context->bundleFullName, '\\', 'Bundle'
        );
        $bundleGenerator->generateClass(
            $classNameDetails->getFullName(),
            $context->bundleClassTemplatePath,
            [
                'use_statements' => $context->useStatements,
                'extension_alias' => $context->extensionAlias,
            ]
        );
    }

    private function generateBundleFiles(Generator $bundleGenerator, ScaffoldContext $context): void
    {
        foreach ($context->fileManifest as [$path, $template, $vars]) {
            $bundleGenerator->generateFile($path, $template, $vars);
        }
    }
}
