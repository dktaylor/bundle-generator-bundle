<?php

namespace Dktaylor\BundleGeneratorBundle;

use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class ComposerQuestionManager
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

        $initComposer = $helper->ask($input, $output, $this->askToInitComposer());
        if (!$initComposer) {
            $this->answerCollection->with(['initComposer' => $initComposer]);
        }

        return $this->answerCollection->with([
            'initComposer' => $initComposer,
            'author' => $helper->ask($input, $output, $this->askForAuthor()),
            'description' => $helper->ask($input, $output, $this->askForDescription()),
        ]);
    }

    private function askForAuthor(): Question
    {
        $question = new Question("\r\nEnter author name (e.g. Jane Smith): ");
        $question->setValidator(function (string $answer) {
            if(empty($answer)) {
                throw new \RuntimeException("The author name can not be empty");
            }

            return $answer;
        });
        $question->setMaxAttempts(3);

        return $question;
    }

    private function askForDescription(): Question
    {
        return new Question("\r\nEnter a description []: ");
    }

    private function askToInitComposer(): Question
    {
        return new ConfirmationQuestion("\r\nShould composer be initialized for this bundle? [y]");
    }
}