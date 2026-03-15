<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker\ValueObject;

use Dktaylor\BundleGeneratorBundle\Symfony\Maker\TemplateResolverInterface;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final readonly class ScaffoldContext
{
    public function __construct(
        public string $bundleFullName,
        public string $namespacePrefix,
        public string $packageName,
        public string $vendor,
        public string $bundleShortName,
        public string $bundleDir,
        public string $rootDirectory,
        public string $templatePath,
        public string $extensionAlias,
        public bool   $initComposer,
        public string $authorName,
        public string $bundleDescription,
        public string $bundleClassTemplatePath,
        public UseStatementGenerator $useStatements,
    ) {}

    public static function fromInput(
        InputInterface $input,
        Generator $generator,
        TemplateResolverInterface $templateResolver,
    ): self {
        $bundleFullName = $input->getArgument('bundle');
        $words = self::splitBundleNameWords($bundleFullName);

        self::assertWordCount($words, $bundleFullName);

        $namespacePrefix = trim($words[0] . '\\' . $words[1] . $words[2], '\\');
        $packageName = strtolower(trim($words[0] . '/' . $words[1] .'_' . $words[2], '/\\'));
        $vendor = $words[0];
        $bundleShortName = trim($words[1] . $words[2], '\\');
        $bundleDir = self::resolveBundleDirectory($generator->getRootDirectory(), $bundleFullName);

        if (!preg_match('/^[a-z0-9_.-]+\/[a-z0-9_.-]+$/', $packageName)) {
            throw new RuntimeCommandException(
                sprintf('Derived package name "%s" is not a valid composer package name.', $packageName)
            );
        }

        return new self(
            bundleFullName: $bundleFullName,
            namespacePrefix: $namespacePrefix,
            packageName: $packageName,
            vendor: $vendor,
            bundleShortName: $bundleShortName,
            bundleDir: $bundleDir,
            rootDirectory: $generator->getRootDirectory(),
            templatePath: $templateResolver->getTemplatesDirectory(),
            extensionAlias: $input->getOption('extension-alias'),
            initComposer: $input->getOption('do-init-composer'),
            authorName: $input->getOption('author-name') ?? '',
            bundleDescription: $input->getOption('bundle-description') ?? '',
            bundleClassTemplatePath: $templateResolver->resolve('bundle/Bundle.tpl.php'),
            useStatements: self::buildUseStatements(),
        );
    }

    private static function assertWordCount(array $words, string $bundleFullName): void
    {
        if (3 > count($words)) {
            throw new RuntimeCommandException(
                sprintf(
                    'Could not derive at least 3 name segments from "%s". Ensure the bundle name follows the patther e.g. AcmeDemoBundle.',
                    $bundleFullName
                )
            );
        }
    }

    /**
     * Resolves and validates the bundle directory, preventing path traversal.
     *
     * @throws RuntimeCommandException if the resolved path escapes the project root.
     */
    private static function resolveBundleDirectory(string $rootDirectory, string $bundleFullName): string
    {
        // Strip any path separators from the bundle name before use in a path
        $safeName = self::ensureSafeName($bundleFullName);
        $projectRoot = dirname($rootDirectory);
        $bundleDir = $projectRoot . '/' . $safeName;
        $resolved = file_exists($bundleDir) ? realpath($bundleDir) : $bundleDir;

        if (!str_starts_with($resolved, $projectRoot . '/')) {
            throw new RuntimeCommandException(
                sprintf('Resolved bundle path "%s" is outside the project root directory.', $resolved)
            );
        }

        return $bundleDir;
    }

    private static function ensureSafeName(string $bundleFullName): string
    {
        $safeName = basename($bundleFullName);

        if ($safeName !== $bundleFullName) {
            throw new RuntimeCommandException(
                sprintf('Bundle name "%s" is invalid or contains illegal characters.', $bundleFullName)
            );
        }

        return $safeName;
    }

    private static function buildUseStatements(): UseStatementGenerator
    {
        return new UseStatementGenerator([
            AbstractBundle::class,
            ContainerConfigurator::class,
            ContainerBuilder::class,
            DefinitionConfigurator::class,
            Definition::class,
            AttributeDriver::class,
        ]);
    }

    private static function splitBundleNameWords(string $bundle): array
    {
        return explode(' ', Str::asHumanWords(Str::asCamelCase($bundle)));
    }
}
