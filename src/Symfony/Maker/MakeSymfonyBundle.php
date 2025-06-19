<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ComposerAutoloaderFinder;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Asset\Exception\LogicException;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @method string getCommandDescription()
 */
class MakeSymfonyBundle extends AbstractMaker
{
    public function __construct(
        private readonly GeneratorFactory $generatorFactory,
        private readonly Filesystem $filesystem,
        private readonly ComposerAutoloaderFinder $composerAutoloaderFinder,
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
            ->addArgument('bundle', InputArgument::OPTIONAL, 'What is the full name of the bundle? (e.g. <fg=yellow>AcmeDemoBundle</>)')
            ->addOption('extension-alias', null, InputOption::VALUE_REQUIRED, 'What extension alias should be used by the bundle? (e.g. <fg=yellow>acme_demo</>)')
            ->addOption('do-init-composer', null, InputOption::VALUE_NONE, 'Should composer be initialized for the bundle?')
            ->setHelp($this->getMakerHelpFileContents('MakeSymfonyBundle.txt'));

        $inputConfig->setArgumentAsNonInteractive('bundle');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $bundleInputWordSplitter = $this->getBundleNameWordSplitter();

        $argument = $command->getDefinition()->getArgument('bundle');
        $question = new Question($argument->getDescription());
        $question->setValidator(self::validateFullBundleName(...));
        $bundle ??= $io->askQuestion($question);
        $input->setArgument('bundle', $bundle);

        $words = $bundleInputWordSplitter($input->getArgument('bundle'));
        $defaultExtensionAlias = Str::asSnakeCase($words[0].$words[1]);
        if (null === $input->getOption('extension-alias')) {
            $extensionAlias = $io->ask(
                $command->getDefinition()->getOption('extension-alias')->getDescription(),
                $defaultExtensionAlias,
                Validator::notBlank(...)
            );
            $input->setOption('extension-alias', $extensionAlias);
        }

        $doInitComposer = $io->confirm(
            'Do you want to initialize composer for the bundle?',
            true,
        );
        $input->setOption('do-init-composer', $doInitComposer);


    }

    /**
     * @inheritDoc
     */
    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $bundleInputWordSplitter = $this->getBundleNameWordSplitter();
        $words = $bundleInputWordSplitter($input->getArgument('bundle'));
        $namespacePrefix = trim($words[0].'\\'.$words[1].$words[2], '\\');
        $bundleShortName = trim($words[1].$words[2], '\\');

        // Create a custom generator with the Prefix of the specific bundle.
        // Don't overwrite the original generator in case it is needed.
        $bundleDir = dirname($generator->getRootDirectory()). '/' . $bundleShortName;
        $bundleGenerator = $this->generatorFactory->create(
            $namespacePrefix,
            $bundleDir,
        );

        // We have to fool the AutoloaderUtil in FileManager
        $classLoader = $this->composerAutoloaderFinder->getClassLoader();
        $psr4Namespace = $bundleGenerator->getRootNamespace().'\\';
        $psr4Src = $bundleGenerator->getRootDirectory(). '/src/';
        $classLoader->addPsr4($psr4Namespace, $psr4Src);

        $useStatements = new UseStatementGenerator([
            AbstractBundle::class,
            ContainerConfigurator::class,
            ContainerBuilder::class,
            DefinitionConfigurator::class,
            DoctrineOrmMappingsPass::class,
            Definition::class,
            AttributeDriver::class
        ]);

        $entityClassDetails = $bundleGenerator->createClassNameDetails(
            $bundleShortName,
            '\\',
            'Bundle'
        );

        $targetPath = $bundleGenerator->generateClass(
            $entityClassDetails->getFullName(),
            $this->getTemplatePath('bundle/Bundle.tpl.php'),
            [
                'use_statements' => $useStatements,
                'extension_alias' => $input->getOption('extension-alias'),
            ]
        );

        $bundleGenerator->writeChanges();
    }

    /**
     * @inheritDoc
     */
    public function configureDependencies(DependencyBuilder $dependencies)
    {
    }

    private function getTemplatePath(string $templateName): string
    {
        $path = dirname(__DIR__, 3) . '/templates/';

        $templateFile = $path.$templateName;
        if (!file_exists($templateFile)) {
            throw new LogicException('The template "'.$templateFile.'" does not exist.');
        }

        return $path.$templateName;
    }

    private function getMakerHelpFileContents(string $helpFileName): string
    {
        return file_get_contents(\sprintf('%s/config/help/%s', \dirname(__DIR__, 3), $helpFileName));
    }

    private function getBundleNameWordSplitter(): callable
    {
        return function (string $bundle): bool|array {
            return explode(" ", Str::asHumanWords(Str::asCamelCase($bundle)));
        };
    }

    public static function validateFullBundleName(?string $value = null): string
    {
        $value = Validator::notBlank($value);

        $words = explode(" ", Str::asHumanWords(Str::asCamelCase($value)));
        if (!$words) {
            throw new RuntimeCommandException('Unable to parse bundle name.');
        }
        if (count($words) < 3) {
            throw new RuntimeCommandException('The bundle name must contain at least 3 words distinguishable by uppercase alphabet characters. e.g. AcmeDemoBundle');
        }
        if (end($words) !== 'Bundle') {
            throw new RuntimeCommandException('The bundle name must end with \'Bundle\'');
        }

        return $value;
    }
}