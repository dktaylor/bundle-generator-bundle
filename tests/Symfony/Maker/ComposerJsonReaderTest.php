<?php

namespace Dktaylor\BundleGeneratorBundle\Tests\Symfony\Maker;

use Dktaylor\BundleGeneratorBundle\Symfony\Maker\ComposerJsonReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Component\Filesystem\Filesystem;

class ComposerJsonReaderTest extends TestCase
{
    private string $tempDir;
    private ComposerJsonReader $reader;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/composer_json_reader_test_' . uniqid();
        mkdir($this->tempDir, 0o755, true);

        $this->reader = new ComposerJsonReader(new Filesystem());
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    // ------------------------------------------------------------------------
    // Happy path
    // ------------------------------------------------------------------------

    #[Test]
    public function testReadsAndParsesValidComposerJson(): void
    {
        $this->writeComposerJson(['name' => 'acme/demo-bundle', 'type' => 'symfony-bundle']);

        $data = $this->reader->read($this->tempDir);

        $this->assertSame('acme/demo-bundle', $data['name']);
        $this->assertSame('symfony-bundle', $data['type']);
    }

    #[Test]
    public function testReturnsCachedResultOnRepeatedCalls(): void
    {
        $this->writeComposerJson(['name' => 'acme/demo-bundle']);

        $first = $this->reader->read($this->tempDir);

        // Overwrite the file - cached result should be returned, not the new content
        $this->writeComposerJson(['name' => 'acme/modified-bundle']);

        $second = $this->reader->read($this->tempDir);

        $this->assertSame($first, $second);
        $this->assertSame('acme/demo-bundle', $second['name']);
    }

    // ------------------------------------------------------------------------
    // Missing or unreadable file
    // ------------------------------------------------------------------------

    #[Test]
    public function testThrowsWhenComposerJsonDoesNotExist(): void
    {
        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessage('composer.json');

        $this->reader->read($this->tempDir);
    }

    #[Test]
    public function testThrowsWhenComposerJsonIsNotReadable(): void
    {
        $this->writeComposerJson(['name' => 'acme/demo-bundle'], permissions: 0o000);

        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessage('composer.json');

        $this->reader->read($this->tempDir);
    }

    #[Test]
    public function testThrowsWhenComposerContainsInvalidJson(): void
    {
        $this->writeRaw('{ this is not valid json }');

        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessage('invalid JSON');

        $this->reader->read($this->tempDir);
    }

    #[Test]
    public function testThrowsWhenComposerJsonContainsJsonArrayInsteadOfObject(): void
    {
        $this->writeRaw('["acme", "demo-bundle"]');

        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessage('does not contain an object');

        $this->reader->read($this->tempDir);
    }

    #[Test]
    public function testThrowsWhenComposerJsonContainsJsonStringInsteadOfObject(): void
    {
        $this->writeRaw('"just a string"');

        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessage('does not contain an object');

        $this->reader->read($this->tempDir);
    }

    #[Test]
    public function testThrowsWhenComposerJsonIsEmpty(): void
    {
        $this->writeRaw('');

        $this->expectException(RuntimeCommandException::class);

        $this->reader->read($this->tempDir);
    }

    // ------------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------------

    private function writeComposerJson(array $data, int $permissions = 0o644): void
    {
        $path = $this->tempDir . '/composer.json';
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
        chmod($path, $permissions);
    }

    private function writeRaw(string $content): void
    {
        file_put_contents($this->tempDir . '/composer.json', $content);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($path);
    }
}
