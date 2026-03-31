<?php

namespace Dktaylor\BundleGeneratorBundle\Tests\Symfony\Maker;

use Composer\Autoload\ClassLoader;
use Dktaylor\BundleGeneratorBundle\Symfony\Maker\BundleIOInterface;
use Dktaylor\BundleGeneratorBundle\Symfony\Maker\ClassLoaderFinderInterface;
use Dktaylor\BundleGeneratorBundle\Symfony\Maker\ComposerManagerInterface;
use Dktaylor\BundleGeneratorBundle\Symfony\Maker\FilesystemManagerInterface;
use Dktaylor\BundleGeneratorBundle\Symfony\Maker\GeneratorFactoryInterface;
use Dktaylor\BundleGeneratorBundle\Symfony\Maker\Scaffolder;
use Dktaylor\BundleGeneratorBundle\Symfony\Maker\TemplateResolverInterface;
use Dktaylor\BundleGeneratorBundle\Symfony\Maker\UseStatementsInterface;
use Dktaylor\BundleGeneratorBundle\Symfony\Maker\ValueObject\GeneratorFactoryContext;
use Dktaylor\BundleGeneratorBundle\Symfony\Maker\ValueObject\ScaffoldContext;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;

class ScaffolderTest extends TestCase
{
    private GeneratorFactoryInterface $generatorFactory;
    private ComposerManagerInterface $composerManager;
    private FilesystemManagerInterface $filesystemManager;
    private TemplateResolverInterface $templateResolver;
    private ClassLoaderFinderInterface $composerAutoloaderFinder;
    private BundleIOInterface $io;
    private Generator $bundleGenerator;
    private Scaffolder $scaffolder;

    protected function setUp(): void
    {
        $this->generatorFactory = $this->createMock(GeneratorFactoryInterface::class);
        $this->composerManager = $this->createMock(ComposerManagerInterface::class);
        $this->filesystemManager = $this->createMock(FilesystemManagerInterface::class);
        $this->templateResolver = $this->createMock(TemplateResolverInterface::class);
        $this->composerAutoloaderFinder = $this->createMock(ClassLoaderFinderInterface::class);
        $this->io = $this->createMock(BundleIOInterface::class);
        $this->bundleGenerator = $this->createMock(Generator::class);

        $this->scaffolder = new Scaffolder(
            $this->generatorFactory,
            $this->composerManager,
            $this->filesystemManager,
            $this->templateResolver,
            $this->composerAutoloaderFinder,
        );
    }

    #[Test]
    public function testScaffoldAFullBundleWithComposerInit(): void
    {
        $context = $this->makeContext(initComposer: true);

        $this->expectGeneratorCreated($context);
        $this->expectPsr4Registered();
        $this->expectBundleClassGenerated($context);
        $this->expectBundleFilesGenerated();
        $this->expectWriteChanges();

        $this->composerManager
            ->expects($this->once())
            ->method('init')
            ->with($context, $this->io);

        $this->filesystemManager
            ->expects($this->once())
            ->method('createLibSymlink')
            ->with($context->bundleDir, $context->rootDirectory, $context->bundleFullName, $this->io);

        $this->composerManager
            ->expects($this->once())
            ->method('hasLibRepo')
            ->with($context->rootDirectory)
            ->willReturn(false);

        $this->composerManager
            ->expects($this->once())
            ->method('addLibRepo')
            ->with($context->rootDirectory, $this->io);

        $this->scaffolder->scaffold($context, $this->io);
    }

    #[Test]
    public function testSkipsComposerInitWhenNotRequested(): void
    {
        $context = $this->makeContext(initComposer: false);

        $this->expectGeneratorCreated($context);
        $this->expectPsr4Registered();
        $this->expectBundleClassGenerated($context);
        $this->expectBundleFilesGenerated();
        $this->expectWriteChanges();

        $this->composerManager
            ->expects($this->never())
            ->method('init')
        ;

        $this->filesystemManager
            ->expects($this->once())
            ->method('createLibSymlink')
        ;

        $this->composerManager
            ->method('hasLibRepo')
            ->willReturn(false)
        ;

        $this->composerManager
            ->expects($this->once())
            ->method('addLibRepo')
        ;

        $this->scaffolder->scaffold($context, $this->io);
    }

    #[Test]
    public function testSkipsAddingLibRepoWhenAlreadyRegistered(): void
    {
        $context = $this->makeContext(initComposer: false);

        $this->expectGeneratorCreated($context);
        $this->expectPsr4Registered();
        $this->expectBundleClassGenerated($context);
        $this->expectBundleFilesGenerated();
        $this->expectWriteChanges();

        $this->filesystemManager
            ->expects($this->once())
            ->method('createLibSymlink')
        ;

        $this->composerManager
            ->method('hasLibRepo')
            ->with($context->rootDirectory)
            ->willReturn(true)
        ;

        $this->composerManager
            ->expects($this->never())
            ->method('addLibRepo')
        ;

        $this->scaffolder->scaffold($context, $this->io);
    }

    #[Test]
    public function testGeneratesBundleFilesFromResolvedTemplatePaths(): void
    {
        $context = $this->makeContext(initComposer: false);

        $this->expectGeneratorCreated($context);
        $this->expectPsr4Registered();
        $this->expectBundleClassGenerated($context);
        $this->expectWriteChanges();

        $this->templateResolver
            ->method('resolve')
            ->willReturnMap([
                ['bundle/Bundle.tpl.php', '/templates/bundle/Bundle.tpl.php'],
                ['bundle/DocIndex.tpl.php', '/templates/bundle/DocIndex.tpl.php'],
                ['bundle/Readme.tpl.php', '/templates/bundle/Readme.tpl.php'],
                ['bundle/Services.tpl.php', '/templates/bundle/Services.tpl.php'],
            ])
        ;

        $this->bundleGenerator
            ->expects($this->exactly(3))
            ->method('generateFile')
            ->willReturnCallback(function (string $path, string $template, array $vars) use ($context): void {
                $this->assertStringContainsString($context->bundleDir, $path);
                $this->assertStringstartsWith('/templates/bundle/', $template);
            })
        ;

        $this->filesystemManager->method('createLibSymlink');
        $this->composerManager->method('hasLibRepo')->willReturn(true);

        $this->scaffolder->scaffold($context, $this->io);
    }

    #[Test]
    public function testCorrectPsr4NamespacesAreRegistered(): void
    {
        $context = $this->makeContext(initComposer: false);

        $this->bundleGenerator->method('getRootNamespace')->willReturn('Acme\\DemoBundle');
        $this->bundleGenerator->method('getRootDirectory')->willReturn($context->bundleDir);

        $this->generatorFactory
            ->method('create')
            ->willReturn($this->bundleGenerator)
        ;

        $classLoader = $this->createMock(ClassLoader::class);
        $classLoader
            ->expects($this->once())
            ->method('addPsr4')
            ->with('Acme\\DemoBundle\\', $context->bundleDir . '/src/')
        ;

        $this->composerAutoloaderFinder
            ->method('getClassLoader')
            ->willReturn($classLoader)
        ;

        $classNameDetails = new ClassNameDetails('AcmeDemoBundle', 'Acme\\DemoBundle\\', 'Bundle');
        $this->bundleGenerator
            ->method('createClassNameDetails')
            ->willReturn($classNameDetails)
        ;

        $this->bundleGenerator->method('generateClass');
        $this->bundleGenerator->method('generateFile');
        $this->bundleGenerator->method('writeChanges');

        $this->filesystemManager->method('createLibSymlink');
        $this->composerManager->method('hasLibRepo')->willReturn(true);

        $this->scaffolder->scaffold($context, $this->io);
    }

    // ----------------------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------------------

    private function makeContext(bool $initComposer): ScaffoldContext
    {
        $useStatements = $this->createMock(UseStatementsInterface::class);
        $useStatements->method('unwrap')->willReturn(new UseStatementGenerator([]));

        return new ScaffoldContext(
            bundleFullName: 'AcmeDemoBundle',
            namespacePrefix: 'Acme\\Demo',
            packageName: 'acme/demo_bundle',
            vendor:'DemoBundle',
            bundleShortName: 'DemoBundle',
            bundleDir: '/project/AcmeDemoBundle',
            rootDirectory: '/project/app',
            templatePath: '/project/templates',
            extensionAlias: 'acme_demo',
            initComposer: $initComposer,
            authorName: 'Jane Smith',
            bundleDescription: 'A demo bundle.',
            bundleClassTemplatePath: '/templates/bundle/Bundle.tpl.php',
            useStatements: $useStatements,
        );
    }

    private function expectGeneratorCreated(ScaffoldContext $context): void
    {
        $this->generatorFactory
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function (GeneratorFactoryContext $ctx) use ($context): bool {
                return $ctx->namespacePrefix === $context->namespacePrefix
                    && $ctx->bundleDir === $context->bundleDir
                    && $ctx->templatePath === $context->templatePath;
            }))
            ->willReturn($this->bundleGenerator)
        ;
    }

    private function expectPsr4Registered(): void
    {
        $this->bundleGenerator->method('getRootNamespace')->willReturn('Acme\\DemoBundle');
        $this->bundleGenerator->method('getRootDirectory')->willReturn('/project/AcmeDemoBundle');

        $classLoader = $this->createMock(ClassLoader::class);
        $this->composerAutoloaderFinder->method('getClassLoader')->willReturn($classLoader);
    }

    private function expectBundleClassGenerated(ScaffoldContext $context): void
    {
        $classNameDetails = new ClassNameDetails('AcmeDemoBundle', 'Acme\\DemoBundle\\', 'Bundle');

        $this->bundleGenerator
            ->expects($this->once())
            ->method('createClassNameDetails')
            ->with($context->bundleFullName, '\\', 'Bundle')
            ->willReturn($classNameDetails)
        ;

        $this->bundleGenerator
            ->expects($this->once())
            ->method('generateClass')
            ->with(
                $classNameDetails->getFullName(),
                $context->bundleClassTemplatePath,
                $this->callback(fn(array $vars): bool =>
                    isset($vars['use_statements'], $vars['extension_alias'])
                    && $vars['extension_alias'] === $context->extensionAlias
                )
            )
        ;
    }

    private function expectBundleFilesGenerated(): void
    {
        $this->templateResolver
            ->method('resolve')
            ->willReturnCallback(fn(string $t): string => '/templates/' . $t)
        ;

        $this->bundleGenerator
            ->expects($this->exactly(3))
            ->method('generateFile')
        ;
    }

    private function expectWriteChanges(): void
    {
        $this->bundleGenerator
            ->expects($this->once())
            ->method('writeChanges')
        ;
    }
}
