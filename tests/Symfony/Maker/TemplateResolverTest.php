<?php

namespace Dktaylor\BundleGeneratorBundle\Tests\Symfony\Maker;

use Dktaylor\BundleGeneratorBundle\Symfony\Maker\TemplateResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;

class TemplateResolverTest extends TestCase
{
    private string $tempDir;
    private TemplateResolver $resolver;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/template_resolver_test_' . uniqid();
        mkdir($this->tempDir . '/templates/bundle', 0o755, true);

        $this->resolver = new TemplateResolver($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    // ------------------------------------------------------------------------
    // getTemplateDirectory()
    // ------------------------------------------------------------------------

    #[Test]
    public function testReturnsTheTemplateDirectory(): void
    {
        $this->assertSame(
            $this->tempDir . '/templates',
            $this->resolver->getTemplatesDirectory()
        );
    }

    #[Test]
    public function testNormalizesTrailingSlashOnBundleRootDir(): void
    {
        $resolver = new TemplateResolver($this->tempDir . '/');

        $this->assertSame(
            $this->tempDir . '/templates',
            $resolver->getTemplatesDirectory()
        );
    }

    // ------------------------------------------------------------------------
    // resolve()
    // ------------------------------------------------------------------------

    #[Test]
    public function testResolvesValidTemplatePath(): void
    {
        $this->createTemplate('bundle/Bundle.tpl.php');

        $resolved = $this->resolver->resolve('bundle/Bundle.tpl.php');

        $this->assertSame(
            $this->tempDir . '/templates/bundle/Bundle.tpl.php',
            $resolved
        );
    }

    #[Test]
    public function testNormalizesLeadingSlashOnTemplateName(): void
    {
        $this->createTemplate('bundle/Bundle.tpl.php');

        $resolved = $this->resolver->resolve('/bundle/Bundle.tpl.php');

        $this->assertSame(
            $this->tempDir . '/templates/bundle/Bundle.tpl.php',
            $resolved
        );
    }

    #[Test]
    public function testThrowsWhenTemplateDoesNotExist(): void
    {
        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessage('bundle/Missing.tpl.php');

        $this->resolver->resolve('bundle/Missing.tpl.php');
    }

    public function testThrowsWhenTemplateIsNotReadable(): void
    {
        $this->createTemplate('bundle/Unreadable.tpl.php', permissions: 0o000);

        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessage('bundle/Unreadable.tpl.php');

        $this->resolver->resolve('bundle/Unreadable.tpl.php');
    }

    // ------------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------------

    private function createTemplate(string $relativePath, int $permissions = 0o644): void
    {
        $fullPath = $this->tempDir . '/templates/' . $relativePath;
        $dir = dirname($fullPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }

        file_put_contents($fullPath, '<?php // template ?>');
        chmod($fullPath, $permissions);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($path);
    }
}
