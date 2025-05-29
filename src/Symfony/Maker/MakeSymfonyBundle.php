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
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\Process\Process;

/**
 * @method string getCommandDescription()
 */
class MakeSymfonyBundle extends AbstractMaker
{
    public function __construct(
        private readonly GeneratorFactory $generatorFactory,
        private readonly ComposerAutoloaderFinder $composerAutoloaderFinder,
        private readonly Filesystem $filesystem,
    ) {}

    /**
     * @inheritDoc
     */
    public static function getCommandName(): string
    {
        return 'make:bundle';
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
            ->addOption('author-name', null, InputOption::VALUE_NONE, 'The name of the author; used if composer-init is true')
            ->addOption('bundle-description', null, InputOption::VALUE_NONE, 'A brief description of the bundle')
            ->setHelp($this->getMakerHelpFileContents('MakeSymfonyBundle.txt'));

        $inputConfig->setArgumentAsNonInteractive('bundle');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $bundleInputWordSplitter = $this->getBundleNameWordSplitter();

        $argument = $command->getDefinition()->getArgument('bundle');
        $bundleNameQuestion = new Question($argument->getDescription());
        $bundleNameQuestion->setValidator(self::validateFullBundleName(...));
        $bundle ??= $io->askQuestion($bundleNameQuestion);
        $input->setArgument('bundle', $bundle);

        $words = $bundleInputWordSplitter($input->getArgument('bundle'));
        $defaultExtensionAlias = Str::asSnakeCase($words[0].$words[1]);
        if (null === $input->getOption('extension-alias')) {
            $input->setOption('extension-alias', $defaultExtensionAlias);
        }

        $doInitComposer = $io->confirm(
            'Do you want to initialize composer for the bundle?',
            true,
        );
        $input->setOption('do-init-composer', $doInitComposer);

        if ($doInitComposer) {
            $authorNameQuestion = new Question('Author Name? (e.g. Jane Smith)');
            $authorNameQuestion->setValidator(self::validateComposerAuthorName(...));
            $authorName = $io->askQuestion($authorNameQuestion);
            $input->setOption('author-name', $authorName);

            $descriptionArgument = $command->getDefinition()->getOption('bundle-description');
            $projDescriptionQuestion = new Question($descriptionArgument->getDescription());
            $projDescriptionQuestion->setValidator(self::validateBundleDescription(...));
            $projDescription = $io->askQuestion($projDescriptionQuestion);
            $input->setOption('bundle-description', $projDescription);
        }
    }

    /**
     * @inheritDoc
     */
    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $bundleInputWordSplitter = $this->getBundleNameWordSplitter();
        $bundleFullName = $input->getArgument('bundle');
        $words = $bundleInputWordSplitter($bundleFullName);
        $namespacePrefix = trim($words[0].'\\'.$words[1].$words[2], '\\');
        $packageName = strtolower(trim($words[0].'/'.$words[1].'_'.$words[2], '\\'));
        $bundleShortName = trim($words[1].$words[2], '\\');

        // Create a custom generator with the Prefix of the specific bundle.
        // Don't overwrite the original generator in case it is needed.
        $bundleDir = dirname($generator->getRootDirectory()). '/' . $bundleFullName;
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

        $bundleClassNameDetails = $bundleGenerator->createClassNameDetails(
            $bundleFullName,
            '\\',
            'Bundle'
        );

        $bundleGenerator->generateClass(
            $bundleClassNameDetails->getFullName(),
            $this->getTemplatePath('bundle/Bundle.tpl.php'),
            [
                'use_statements' => $useStatements,
                'extension_alias' => $input->getOption('extension-alias'),
            ]
        );

        $bundleGenerator->generateFile(
            $bundleDir.'/docs/index.rst',
            $this->getTemplatePath('bundle/DocIndex.tpl.php'),
            [
                'vendor' => $words[0],
                'bundleShortName' => $bundleShortName,
            ]
        );

        $bundleGenerator->generateFile(
            $bundleDir.'/README.md',
            $this->getTemplatePath('bundle/Readme.tpl.php'),
            [
                'vendor' => $words[0],
                'bundleShortName' => $bundleShortName,
            ]
        );

        $bundleGenerator->generateFile(
            $bundleDir.'/config/services.xml',
            $this->getTemplatePath('bundle/Services.tpl.php')
        );

        $bundleGenerator->writeChanges();

        $phpMajorMinor = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
        $initBundleCmd = [
            "composer",
            "init",
            "--no-interaction",
            "--working-dir={$bundleDir}",
            "--name={$packageName}",
            "--description={$input->getOption('bundle-description')}",
            "--author={$input->getOption('author-name')}",
            "--type=symfony-bundle",
            "--stability=stable",
            "--autoload=src/",
            "--require=php:^{$phpMajorMinor}",
        ];

        (new Process($initBundleCmd))
            ->setTimeout(60)
            ->run(function ($type, $buffer) use ($io) {
                $io->writeln(($type == Process::OUT ? 'OK' : 'ERR') . ' ' . $buffer);
            })
        ;

        $this->filesystem->symlink($bundleDir, $generator->getRootDirectory(). '/lib/'.$bundleFullName);

        if (!$this->hasLibRepo($generator->getRootDirectory())) {
            $addBundleRepoCmd = [
                "composer",
                "config",
                "--no-interaction",
                "--working-dir={$generator->getRootDirectory()}",
                "repositories.lib",
                "path",
                "lib/*",
            ];

            (new Process($addBundleRepoCmd))
                ->setTimeout(60)
                ->run(function ($type, $buffer) use ($io) {
                    $io->writeln(($type == Process::OUT ? 'OK' : 'ERR') . ' ' . $buffer);
                })
            ;
        }
    }

    /**
     * @inheritDoc
     */
    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            Process::class,
            'process'
        );

        $dependencies->addClassDependency(
            Filesystem::class,
            'filesystem'
        );
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

    public static function validateComposerAuthorName(?string $value = null): string
    {
        $value = Validator::notBlank($value);

        if (strlen($value) < 3) {
            throw new RuntimeCommandException('The author\'s name must be at least 3 characters long.');
        }

        return $value;
    }

    public static function validateBundleDescription(?string $value = null): string
    {
        if (strlen($value) > 100) {
            throw new RuntimeCommandException('The description is too long. Keep the length under 100 characters.');
        }

        return $value;
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

    private function getProjectComposerJsonData(string $directory): array
    {
        $composerJson = file_get_contents($directory. '/composer.json');

        return json_decode($composerJson, true);
    }

    private function hasLibRepo(string $directory): bool
    {
        $composerData = $this->getProjectComposerJsonData($directory);
        if (isset($composerData['repositories'])) {
            $repositories = $composerData['repositories'];
            foreach ($repositories as $repository) {
                if ($repository['url'] === "lib/*") {
                    return true;
                }
            }
        }

        return false;
    }
}
