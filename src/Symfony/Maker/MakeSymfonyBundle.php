<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

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
        $bundleName = $input->getArgument('bundle');
        $words = preg_split('~(?=[A-Z][^A-Z])~', $bundleName, -1, PREG_SPLIT_NO_EMPTY);
        if (count($words) < 3) {
            $io->error('The bundle name must contain at least 3 words distinguishable by uppercase alphabet characters. e.g. AcmeDemoBundle');
        }

        if (end($words) !== 'Bundle') {
            $io->error('The bundle name must end with \'Bundle\'');
        }

        $namespacePrefix = trim($words[0].'\\'.$words[1].$words[2], '\\');
        $bundleName = trim($words[1].$words[2], '\\');
        // Create a custom generator with the Prefix of the specific bundle.
        $generator = $this->generatorFactory->create(
            $namespacePrefix,
            $generator->getRootDirectory() . '/../' . $bundleName
        );
        dump($generator->getRootNamespace());

        $entityClassDetails = $generator->createClassNameDetails(
            'Default',
            'Controller',
            'Controller'
        );

        $fullName = $entityClassDetails->getFullName();
        dump($fullName);

        $rootDir = $generator->getRootDirectory();
        dump($rootDir);
    }
}