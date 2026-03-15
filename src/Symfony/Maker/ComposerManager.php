<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Dktaylor\BundleGeneratorBundle\Symfony\Maker\ValueObject\ScaffoldContext;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Component\Console\Input\InputInterface;

class ComposerManager extends AbstractProcessHandler implements ComposerManagerInterface
{
    private array $composerJsonCache = [];

    public function __construct(
        private readonly ProcessRunnerInterface $processRunner,
        private readonly ComposerJsonReaderInterface $composerJsonReader,
    ) {}

    public function init(ScaffoldContext $context, ConsoleStyle $io): void
    {
        $cmd = $this->buildComposerInitCommand($context);
        $this->processRunner->run($cmd, $io, sprintf('composer init failed in "%s".', $context->bundleDir));
    }

    public function hasLibRepo(string $rootDirectory): bool
    {
        try {
            $composerData = $this->composerJsonReader->read($rootDirectory);
        } catch (RuntimeCommandException) {
            return false;
        }

        foreach ($composerData['repositories'] ?? [] as $repository) {
            if (($repository['url'] ?? null) === 'lib/*') {
                return true;
            }
        }

        return false;
    }

    /**
     * Registers the lib/* path repository in the host project's composer.json.
     *
     * @throws RuntimeCommandException on non-zero exit
     */
    public function addLibRepo(string $rootDirectory, ConsoleStyle $io): void
    {
        $this->processRunner->run(
            [
                'composer', 'config',
                '--no-interaction',
                "--working-dir={$rootDirectory}",
                'repositories.lib', 'path', 'lib/*',
            ],
            $io,
            sprintf('composer config failed in "%s".', $rootDirectory)
        );
    }

    /**
     * Runs 'composer init' in the bundle directory.
     *
     * @throws RuntimeCommandException on non-zero exit
     */
    private function buildComposerInitCommand(ScaffoldContext $context): array
    {
        $phpMajorMinor = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

        $cmd = [
            'composer', 'init',
            '--no-interaction',
            "--working-dir={$context->bundleDir}",
            "--name={$context->packageName}",
            '--type=symfony-bundle',
            '--stability=stable',
            '--autoload=src/',
            "--require=php:^{$phpMajorMinor}",
        ];

        if ('' !== $context->bundleDescription) {
            $cmd[] = '--description';
            $cmd[] = $context->bundleDescription;
        }

        if ('' !== $context->authorName) {
            $cmd[] = '--author';
            $cmd[] = $context->authorName;
        }

        return $cmd;
    }
}
