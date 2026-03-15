<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Component\Process\Process;

class AbstractProcessHandler
{
    /**
     * @param list<string> $cmd
     * @throws RuntimeCommandException on non-zero exit
     */
    public function runProcess(array $cmd, ConsoleStyle $io, string $failureMessage): void
    {
        $process = (new Process($cmd))->setTimeout(60);

        $process->run(static function (string $type, string $buffer) use ($io): void {
            $io->writeln((($type === Process::OUT) ? 'OK' : 'ERR') . ' ' . $buffer);
        });

        if (!$process->isSuccessful()) {
            throw new RuntimeCommandException(
                sprintf("%s\nExit code: %d\nOutput: %s", $failureMessage, $process->getExitCode(), $process->getErrorOutput())
            );
        }
    }
}