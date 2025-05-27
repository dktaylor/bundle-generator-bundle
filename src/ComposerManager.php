<?php

namespace Dktaylor\BundleGeneratorBundle;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ComposerManager
{
    private ?AnswerCollection $answers = null;

    public function setAnswers(AnswerCollection $answers): void
    {
        $this->answers = $answers;
    }

    public function build(string $bundleDir, OutputInterface $output): void
    {
        if (!$this->answers->get('initComposer')) {
            $output->writeln("\r\nSkipping composer initialization");
            return;
        }

        $vendorName = $this->answers->get('vendorName');
        $bundleName = $this->answers->get('bundleName');
        $description = $this->answers->get('description');
        $author = $this->answers->get('author');

        $kebabCase = StringUtility::convertString($bundleName, StringUtility::STRING_PASCAL_TO_KEBAB);
        $packageName = strtolower($vendorName . "/" . $kebabCase);
        $cmd = [
            "composer",
            "init",
            "--no-interaction",
            "--working-dir=$bundleDir",
            "--name=$packageName",
            "--description=$description",
            "--author=$author",
            "--type=symfony-bundle",
            "--stability=stable",
            "--autoload=src/",
        ];

        $process = new Process($cmd);
        $process->setTimeout(60);
        $process->run(
            function ($type, $buffer) use ($output) {
                $output->writeln((Process::ERR === $type) ? 'ERR:' . $buffer : $buffer);
            }
        );
    }
}