<?php

namespace Dktaylor\BundleGeneratorBundle\Command;


use Dktaylor\BundleGeneratorBundle\AnswerCollection;
use Dktaylor\BundleGeneratorBundle\BundleManager;
use Dktaylor\BundleGeneratorBundle\BundleQuestionManager;
use Dktaylor\BundleGeneratorBundle\ComposerManager;
use Dktaylor\BundleGeneratorBundle\ComposerQuestionManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'dktaylor:generate-bundle-skeleton',
    description: 'Generate a Symfony bundle skeleton for a new bundle',
)]
class GenerateBundleSkeletonCommand extends Command
{
    public function __construct(
        private readonly BundleQuestionManager   $bundleQuestionManager,
        private readonly ComposerQuestionManager $composerQuestionManager,
        private readonly BundleManager           $bundleManager,
        private readonly ComposerManager         $composerManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $bundleAnswers = $this->bundleQuestionManager->ask($input, $output);
        $this->bundleManager->setAnswers($bundleAnswers);
        $this->bundleManager->build();

        $composerAnswers = $this->composerQuestionManager->ask($input, $output);
        $answers = new AnswerCollection(array_merge($bundleAnswers->getArrayCopy(), $composerAnswers->getArrayCopy()));
        $this->composerManager->setAnswers($answers);
        $this->composerManager->build($this->bundleManager->getBundleDir(), $output);

        $io->writeln("\r\nNext Steps:");
        $io->writeln("\r\nDo not forget to add the bundle to the main app via /config/bundles.php");
        $io->writeln("\r\nAdd an 'autoload-dev' section to composer.json in the bundle autoloading tests.");
        $io->writeln("\r\nReview the `src/{$answers->get('vendorName')}{$answers->get('bundleName')}.php` file for additional bundle configuration.");
        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
