<?php

namespace Dktaylor\BundleGeneratorBundle\Symfony\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class FilesystemManager extends AbstractProcessHandler implements FilesystemManagerInterface
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly SafeNameResolverInterface $safeNameResolver,
    ) {}

    /**
     * @inheritDoc
     */
    public function createLibSymlink(string $bundleDir, string $rootDirectory, string $bundleFullName, BundleIOInterface $io): void
    {
        $safeName = $this->safeNameResolver->resolve($bundleFullName);
        $symlinkTarget = $rootDirectory . '/lib/' . $safeName;

        try {
            $this->filesystem->symlink($bundleDir, $symlinkTarget);
        } catch (IOException $e) {
            // Rollback the generated directory so we don't leave an orphan scaffold
            $this->filesystem->remove($bundleDir);

            throw new RuntimeCommandException(
                sprintf('Failed to create symlink "%s" -> "%s": %s', $symlinkTarget, $bundleDir, $e->getMessage()),
                previous: $e
            );
        }

        $io->success(sprintf('Created symlink: %s -> %s.', $symlinkTarget, $bundleDir));
    }
}
