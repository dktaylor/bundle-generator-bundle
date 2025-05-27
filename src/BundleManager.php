<?php

namespace Dktaylor\BundleGeneratorBundle;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class BundleManager
{
//    private ?string $libPath = null;

    private ?AnswerCollection $answers = null;

    public function __construct(
        #[AutowireIterator('dktaylor_bundle_generator.directory_generator_handler')]
        private readonly iterable $directoryGeneratorCollection,

        #[AutowireIterator('dktaylor_bundle_generator.file_generator_handler')]
        private readonly iterable $fileGeneratorCollection,

        private readonly Filesystem $filesystem,

        private readonly KernelUtility $kernelUtility,

        private readonly string $libPath,
    ) {
//        $this->libPath = '/lib';
    }

    public function setAnswers(AnswerCollection $answers): void
    {
        $this->answers = $answers;
    }

    public function build(): void
    {
        $bundleDir = $this->getBundleDir();
        $this->createBundleDir();

        foreach ($this->directoryGeneratorCollection as $generator) {
            $generator->create($bundleDir);
        }

        foreach ($this->fileGeneratorCollection as $generator) {
            $generator->create($bundleDir, $this->answers);
        }
    }

    public function getBundleDir(): string
    {
        if(!$this->isInitialized()) {
            $this->notInitialized();
        }

        return $this->kernelUtility->getProjectDir() . '/../' . $this->answers->get('bundleName');
    }

    private function isInitialized(): bool
    {
        return $this->answers !== null && $this->answers->count() > 0;
    }

    private function createBundleDir(): void
    {
        $bundleDir = $this->getBundleDir();
        if ($this->filesystem->exists($bundleDir)) {
            $finder = Finder::create()
                ->in($bundleDir)
                ->depth(0)
            ;

            if ($finder->count() > 0) {
                throw new \RuntimeException(sprintf('The bundle directory "%s" already exists.', $bundleDir));
            }
        }

        $this->filesystem->mkdir($bundleDir);
        $this->filesystem->symlink($bundleDir, $this->kernelUtility->getProjectDir() . '/' . $this->libPath . '/' . $this->answers->get('bundleName'));
    }

    private function notInitialized(): void
    {
        throw new \RuntimeException('Bundle manager has not been initialized yet. Use BundleManager::setAnswers');
    }
}