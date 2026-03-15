<?php

declare(strict_types=1);

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Dktaylor\BundleGeneratorBundle\Symfony\Maker\ValueObject\ScaffoldContext;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * @method string getCommandDescription()
 */
class MakeSymfonyBundle extends AbstractMaker
{
    public function __construct(
        private readonly Scaffolder $scaffolder,
        private readonly TemplateResolverInterface $templateResolver,
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
            ->setHelp($this->templateResolver->resolve('help/MakeSymfonyBundle.txt'))
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

        $doInitComposer = $io->confirm('Do you want to initialize composer for the bundle?', true);
        $input->setOption('do-init-composer', $doInitComposer);

        if ($doInitComposer) {
            $authorNameQuestion = new Question('Author Name? (e.g. Jane Smith)');
            $authorNameQuestion->setValidator(self::validateComposerAuthorName(...));
            $input->setOption('author-name', $io->askQuestion($authorNameQuestion));

            $descriptionOption = $command->getDefinition()->getOption('bundle-description');
            $descriptionQuestion = new Question($descriptionOption->getDescription());
            $descriptionQuestion->setValidator(self::validateBundleDescription(...));
            $input->setOption('bundle-description', $io->askQuestion($descriptionQuestion));
        }
    }

    /**
     * @inheritDoc
     * @throws RuntimeCommandException when the package name is not a valid composer package name
     */
    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $context = ScaffoldContext::fromInput($input, $generator, $this->templateResolver);
        $this->scaffolder->scaffold($context, $io);
    }

    /**
     * @inheritDoc
     */
    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(Process::class, 'process');
        $dependencies->addClassDependency(Filesystem::class, 'filesystem');
    }

    public static function validateFullBundleName(?string $value = null): string
    {
        $value = Validator::notBlank($value);

        $words = explode(' ', Str::asHumanWords(Str::asCamelCase($value)));
        if (!$words) {
            throw new RuntimeCommandException('Unable to parse bundle name.');
        }
        if (count($words) < 3) {
            throw new RuntimeCommandException(
                'The bundle name must contain at least 3 words distinguishable by uppercase alphabet characters. e.g. AcmeDemoBundle'
            );
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

    private static function splitBundleNameWords(string $bundle): array
    {
        return explode(' ', Str::asHumanWords(Str::asCamelCase($bundle)));
    }
}
