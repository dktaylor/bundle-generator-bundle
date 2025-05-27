<?php

namespace Dktaylor\BundleGeneratorBundle;

use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class BundleQuestionManager
{
    private HelperSet $helperSet;

    public function __construct(
        private readonly AnswerCollection $answerCollection,
    )
    {
        $this->helperSet = new HelperSet([
            new QuestionHelper()
        ]);
    }
    public function ask(InputInterface $input, OutputInterface $output): AnswerCollection
    {
        $helper = $this->helperSet->get('question');
        $vendorName = $helper->ask($input, $output, $this->askForVendorName());
        $bundleName = $helper->ask($input, $output, $this->askForBundleName());

        $extensionAlias = $helper->ask($input,$output, $this->askForExtensionAlias($vendorName, $bundleName));
        if ($extensionAlias !== $this->defaultExtensionAlias($vendorName, $bundleName)) {
            $extensionAlias = StringUtility::convertString($extensionAlias, StringUtility::STRING_PASCAL_TO_SNAKE);
        }

        return $this->answerCollection->with([
            'vendorName' => $vendorName,
            'bundleName' => $bundleName,
            'extensionAlias' => $extensionAlias,
        ]);
    }

    private function askForVendorName(): Question
    {
        $question = new Question("\r\nEnter a vendor name (e.g. Acme): ");
        $question->setValidator(function (string $answer) {
            if (empty($answer)) {
                throw new \RuntimeException(
                    'The vendor name can not be empty'
                );
            }

            return $answer;
        });
        $question->setMaxAttempts(3);

        return $question;
    }

    private function askForBundleName(): Question
    {
        $question = new Question("\r\nEnter a bundle name (e.g. FooBundle): ");
        $question->setValidator(function (string $answer) {
            if  (!str_ends_with($answer, 'Bundle')) {
                throw new \RuntimeException(
                    'The name of the bundle should be suffixed wth \'Bundle\''
                );
            }

            return $answer;
        });
        $question->setMaxAttempts(3);

        return $question;
    }

    private function askForExtensionAlias(string $vendorName, string $bundleName): Question
    {
        $extensionAlias = $this->defaultExtensionAlias($vendorName, $bundleName);

        return new Question("\r\nEnter a bundle extension alias [$extensionAlias]: ", $extensionAlias);
    }

    private function defaultExtensionAlias(string $vendorName, string $bundleName): string
    {
        $bundle = substr($bundleName, 0, strpos($bundleName, 'Bundle', -6));

        return StringUtility::convertString($vendorName.$bundle, StringUtility::STRING_PASCAL_TO_SNAKE);
    }

}