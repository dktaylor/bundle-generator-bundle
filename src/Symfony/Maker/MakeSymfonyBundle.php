<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @method string getCommandDescription()
 */
class MakeSymfonyBundle extends AbstractMaker
{
    public function __construct(
        private readonly GeneratorFactory $generatorFactory,
    ) {}

    /**
     * @inheritDoc
     */
    public static function getCommandName(): string
    {
        return 'make:bundle:symfony';
    }

    /**
     * @inheritDoc
     */
    public static function getCommandDescription(): string
    {
        return 'Create a new Symfony bundle';
    }

    /**
     * @inheritDoc
     */
    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->addArgument('bundle', InputArgument::OPTIONAL, 'The name of the bundle (e.g. <fg=yellow>AcmeDemoBundle</>)')
            ->setHelp(file_get_contents(__DIR__.'/Resources/help/MakeSymfonyBundle.txt'));
    }

    /**
     * @inheritDoc
     */
    public function configureDependencies(DependencyBuilder $dependencies)
    {
    }

    /**
     * @inheritDoc
     */
    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $bundle = $input->getArgument('bundle');
        $words = preg_split('~(?=[A-Z][^A-Z])~', $bundle, -1, PREG_SPLIT_NO_EMPTY);
        if (count($words) < 3) {
            $io->error('The bundle name must contain at least 3 words distinguishable by uppercase alphabet characters. e.g. AcmeDemoBundle');
        }

        if (end($words) !== 'Bundle') {
            $io->error('The bundle name must end with \'Bundle\'');
        }

        $defaultShortClassName = Str::getShortClassName($bundle);
        $defaultExtensionAlias = Str::asSnakeCase($defaultShortClassName);
        $extensionAlias = $io->ask('What extension alias should be used?', $defaultExtensionAlias, Validator::notBlank(...));

        $namespacePrefix = trim($words[0].'\\'.$words[1].$words[2], '\\');
        $bundleName = trim($words[1].$words[2], '\\');
        // Create a custom generator with the Prefix of the specific bundle.
        // Don't overwrite the original generator in case it is needed.
        $bundleGenerator = $this->generatorFactory->create(
            $namespacePrefix,
            $generator->getRootDirectory() . '/../' . $bundleName
        );
        dump($bundleGenerator->getRootNamespace());

        $entityClassDetails = $bundleGenerator->createClassNameDetails(
            $bundle,
            '',
            'Bundle'
        );

        $fullName = $entityClassDetails->getFullName();
        dump($fullName);

        $rootDir = $bundleGenerator->getRootDirectory();
        dump($rootDir);

        dump($bundleGenerator->hasPendingOperations() ? "true" : "false");

        $useStatements = new UseStatementGenerator([
            AbstractBundle::class,
            ContainerConfigurator::class,
            ContainerBuilder::class,
            DefinitionConfigurator::class,
            DoctrineOrmMappingsPass::class,
            Definition::class,
            AttributeDriver::class
        ]);

        $bundleGenerator->generateClass(
            $entityClassDetails->getFullName(),
            'bundle/Bundle.tpl.php',
            [
                'use_statements' => $useStatements,
                'extension_alias' => $extensionAlias,
            ]
        );

//        $bundleGenerator->writeChanges();
    }
}