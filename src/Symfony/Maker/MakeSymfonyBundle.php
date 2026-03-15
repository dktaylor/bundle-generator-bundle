<?php

declare(strict_types=1);

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Dktaylor\BundleGeneratorBundle\Symfony\Maker\ValueObject\GeneratorFactoryContext;
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
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\Process\Process;

/**
 * @method string getCommandDescription()
 */
class MakeSymfonyBundle extends AbstractMaker
{
    public function __construct(
        private readonly GeneratorFactoryInterface $generatorFactory,
        private readonly ComposerAutoloaderFinder $composerAutoloaderFinder,
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
    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('bundle', InputArgument::OPTIONAL, 'What is the full name of the bundle? (e.g. <fg=yellow>AcmeDemoBundle</>)')
            ->addOption('extension-alias', null, InputOption::VALUE_REQUIRED, 'What extension alias should be used by the bundle? (e.g. <fg=yellow>acme_demo</>)')
            ->addOption('do-init-composer', null, InputOption::VALUE_NONE, 'Should composer be initialized for the bundle?')
            ->addOption('author-name', null, InputOption::VALUE_OPTIONAL, 'The name of the author; used if composer-init is true')
            ->addOption('bundle-description', null, InputOption::VALUE_OPTIONAL, 'A brief description of the bundle')
            ->setHelp($this->getMakerHelpFileContents('MakeSymfonyBundle.txt'))
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $argument = $command->getDefinition()->getArgument('bundle');
        $bundleNameQuestion = new Question($argument->getDescription());
        $bundleNameQuestion->setValidator(self::validateFullBundleName(...));
        $bundle = $input->getArgument('bundle') ?? $io->askQuestion($bundleNameQuestion);
        $input->setArgument('bundle', $bundle);

        $words = self::splitBundleNameWords($input->getArgument('bundle'));
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
     * @throws RuntimeCommandException when the package name is not a valid composer package name
     */
    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $bundleFullName = $input->getArgument('bundle');
        $words = self::splitBundleNameWords($bundleFullName);

        $namespacePrefix = trim($words[0].'\\'.$words[1].$words[2], '\\');
        $packageName = strtolower(trim($words[0].'/'.$words[1].'_'.$words[2], '/\\'));
        if (!preg_match('/^[a-z0-9_.-]+\/[a-z0-9_.-]+$/', $packageName)) {
            throw new RuntimeCommandException(
                sprintf('Derived package name "%s" is not a valid composer package name.', $packageName)
            );
        }
        $bundleShortName = trim($words[1].$words[2], '\\');

        // Create a custom generator with the Prefix of the specific bundle.
        // Don't overwrite the original generator in case it is needed.
        $bundleDir = $this->resolveBundleDirectory($generator->getRootDirectory(), $bundleFullName);

        $twigPath = $this->ensureFileExistsAndIsReadable(__DIR__ . '/../../../templates');
        $bundleGenerator = $this->generatorFactory->create(new GeneratorFactoryContext($namespacePrefix, $bundleDir, $twigPath));

        // We have to fool the AutoloaderUtil in FileManager
        $classLoader = $this->composerAutoloaderFinder->getClassLoader();
        $psr4Namespace = $bundleGenerator->getRootNamespace().'\\';
        $psr4Src = $bundleGenerator->getRootDirectory() . '/src/';
        $classLoader->addPsr4($psr4Namespace, $psr4Src);

        $useStatements = new UseStatementGenerator([
            AbstractBundle::class,
            ContainerConfigurator::class,
            ContainerBuilder::class,
            DefinitionConfigurator::class,
            Definition::class,
            AttributeDriver::class
        ]);

        $bundleClassNameDetails = $bundleGenerator->createClassNameDetails($bundleFullName, '\\', 'Bundle');

        $bundleGenerator->generateClass(
            $bundleClassNameDetails->getFullName(),
            $this->getTemplatePath('bundle/Bundle.tpl.php'),
            ['use_statements' => $useStatements, 'extension_alias' => $input->getOption('extension-alias')]
        );

        $this->generateFilesFromManifest($bundleGenerator, $bundleDir, $words[0], $bundleShortName);
        $bundleGenerator->writeChanges();

        if ($input->getOption('do-init-composer')) {
            $this->runComposerInit($bundleDir, $packageName, $input, $io);
        }

        $this->createLibSymlink($bundleDir, $generator->getRootDirectory(), $bundleFullName, $io);

        if (!$this->hasLibRepo($generator->getRootDirectory())) {
            $this->runComposerAddLibRepo($generator->getRootDirectory(), $io);
        }
    }

    /**
     * @inheritDoc
     */
    public function configureDependencies(DependencyBuilder $dependencies): void
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

        $words = explode(' ', Str::asHumanWords(Str::asCamelCase($value)));
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

    /**
     * Description is optional - an empty string is accepted intentionally
     */
    public static function validateBundleDescription(string $value): string
    {
        if (strlen($value) > 100) {
            throw new RuntimeCommandException('The description is too long. Keep the length under 100 characters.');
        }

        return $value;
    }

    private function getTemplatePath(string $templateName): string
    {
        $path = dirname(__DIR__, 3) . '/templates/';
        $templateFile = $path . $templateName;

        if (!file_exists($templateFile) || !is_readable($templateFile)) {
            throw new RuntimeCommandException(
                sprintf('Cannot read "%s". Ensure that the file exists and is readable.', $templateFile)
            );
        }

        return $templateFile;
    }



    /**
     * @throws RuntimeCommandException if the help file does not exist or is unreadable
     */
    private function getMakerHelpFileContents(string $helpFileName): string
    {
        static $cache = [];

        if (!isset($cache[$helpFileName])) {
            $file = $this->ensureFileExistsAndIsReadable(
                sprintf('%s/config/help/%s', dirname(__DIR__, 3), $helpFileName)
            );
            $cache[$helpFileName] = $this->retrieveContents($file);
        }

        return $cache[$helpFileName];
    }

    private static function splitBundleNameWords(string $bundle): array
    {
        return explode(" ", Str::asHumanWords(Str::asCamelCase($bundle)));
    }

    /**
     * Resolves and validates the bundle directory, preventing path traversal.
     *
     * @throws RuntimeCommandException if the resolved path escapes the project root.
     */
    private function resolveBundleDirectory(string $rootDirectory, string $bundleFullName): string
    {
        // Strip any path separators from the bundle name before use in a path
        $safeName = $this->ensureSafeName($bundleFullName);

        $projectRoot = dirname($rootDirectory);
        $bundleDir = $projectRoot . '/' . $safeName;

        // Canonicalize if the directory already exists; otherwise verify the
        // intended path stays inside the project root.
        $resolved = file_exists($bundleDir) ? realpath($bundleDir) : $bundleDir;

        if (!str_starts_with($resolved, $projectRoot . '/')) {
            throw new RuntimeCommandException(
                sprintf('Resolved bundle path "%s" is outside the project root directory.', $resolved)
            );
        }

        return $bundleDir;
    }

    /**
     * Returns the list of non-class files to generate for the bundle scaffold.
     *
     * @return array<int, array{0: string, 1: string, 2: array<string, mixed>}>
     */
    private function getBundleFileManifest(string $bundleDir, string $vendor, string $bundleShortName): array
    {
        return [
            [$bundleDir . '/docs/index.rst', 'bundle/DocIndex.tpl.php', ['vendor' => $vendor, 'bundleShortName' => $bundleShortName]],
            [$bundleDir . '/README.md',      'bundle/Readme.tpl.php',   ['vendor' => $vendor, 'bundleShortName' => $bundleShortName]],
            [$bundleDir . '/config/services.xml', 'bundle/Services.tpl.php', []],
        ];
    }

    private function generateFilesFromManifest(Generator $bundleGenerator, string $bundleDir, string $bundleNamespacePrefix, string $bundleShortName): void
    {
        foreach ($this->getBundleFileManifest($bundleDir, $bundleNamespacePrefix, $bundleShortName) as [$path, $template, $vars]) {
            $bundleGenerator->generateFile($path, $this->getTemplatePath($template), $vars);
        }
    }
}
