<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Dktaylor\BundleGeneratorBundle\Symfony\Maker\ValueObject\GeneratorFactoryContext;
use Dktaylor\BundleGeneratorBundle\Symfony\Maker\ValueObject\ScaffoldContext;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Util\ComposerAutoloaderFinder;

class Scaffolder
{
    public function __construct(
        private readonly GeneratorFactoryInterface $generatorFactory,
        private readonly ComposerManagerInterface $composerManager,
        private readonly FilesystemManagerInterface $filesystemManager,
        private readonly TemplateResolverInterface $templateResolver,
        private readonly ComposerAutoLoaderFinder $composerAutoloaderFinder,
    ) {}

    public function scaffold(ScaffoldContext $context, ConsoleStyle $io): void
    {
        $bundleGenerator = $this->buildBundleGenerator($context);

        $this->registerPsr4Namespace($bundleGenerator);
        $this->generateBundleClass($bundleGenerator, $context);
        $this->generateBundleFiles($bundleGenerator, $context);
        $bundleGenerator->writeChanges();

        if ($context->initComposer) {
            $this->composerManager->init($context, $io);
        }

        $this->filesystemManager->createLibSymlink($context->bundleDir, $context->rootDirectory, $context->bundleFullName, $io);

        if (!$this->composerManager->hasLibRepo($context->rootDirectory)) {
            $this->composerManager->addLibRepo($context->rootDirectory, $io);
        }
    }

    private function buildBundleGenerator(ScaffoldContext $context): Generator
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
        $classLoader->addPsr4(
            $bundleGenerator->getRootNamespace() . '\\',
            $bundleGenerator->getRootDirectory() . '/src/',
        );
    }

    private function generateBundleClass(Generator $bundleGenerator, ScaffoldContext $context): void
    {
        $classNameDetails = $bundleGenerator->createClassNameDetails(
            $context->bundleFullName, '\\', 'Bundle'
        );
        $bundleGenerator->generateClass(
            $classNameDetails->getFullName(),
            $context->bundleClassTemplatePath,
            ['use_statements' => $context->useStatements, 'extension_alias' => $context->extensionAlias]
        );
    }

    private function generateBundleFiles(Generator $bundleGenerator, ScaffoldContext $context): void
    {
        foreach ($this->getBundleFileManifest($context) as [$path, $template, $vars]) {
            $bundleGenerator->generateFile($path, $template, $vars);
        }
    }

    /**
     * Returns the list of non-class files to generate for the bundle scaffold.
     *
     * @return array<int, array{0: string, 1: string, 2: array<string, mixed>}>
     */
    private function getBundleFileManifest(ScaffoldContext $context): array
    {
        return [
            [
                $context->bundleDir . '/docs/index.rst',
                $this->templateResolver->resolve('bundle/DocIndex.tpl.php'),
                ['vendor' => $context->vendor, 'bundleShortName' => $context->bundleShortName]
            ],
            [
                $context->bundleDir . '/README.md',
                $this->templateResolver->resolve('bundle/Readme.tpl.php'),
                ['vendor' => $context->vendor, 'bundleShortName' => $context->bundleShortName]
            ],
            [
                $context->bundleDir . '/config/services.xml',
                $this->templateResolver->resolve('bundle/Services.tpl.php'),
                [],
            ],
        ];
    }
}
