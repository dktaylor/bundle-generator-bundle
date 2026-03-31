<?php

namespace Dktaylor\BundleGeneratorBundle\Tests\Symfony\Maker;

use Dktaylor\BundleGeneratorBundle\Symfony\Maker\SafeNameResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;

class SafeNameResolverTest extends TestCase
{
    private SafeNameResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new SafeNameResolver();
    }

    // ---------------------------------------------------------------------------------
    // Valid names - should pass through unchanged
    // ---------------------------------------------------------------------------------

    #[Test]
    #[DataProvider('validNames')]
    public function testReturnsValidNamesUnchanged(string $name): void
    {
        $this->assertSame($name, $this->resolver->resolve($name));
    }

    public static function validNames(): array
    {
        return [
            'plain bundle name' => ['AcmeDemoBundle'],
            'lowercase name' => ['acme'],
            'name with hyphen' => ['acme-demo'],
            'name with underscore' => ['acme_demo'],
            'name with numbers' => ['AcmeDemo2Bundle'],
        ];
    }

    // ---------------------------------------------------------------------------------
    // Invalid names - should throw
    // ---------------------------------------------------------------------------------

    #[Test]
    #[DataProvider('invalidNames')]
    public function testThrowsForNamesContainingInvalidPathCharacters(string $name): void
    {
        $this->expectException(RuntimeCommandException::class);
        $this->resolver->resolve($name);
    }

    public static function invalidNames(): array
    {
        return [
            'forward slash' => ['acme/AcmeDemoBundle'],
            'backslash' => ['acme\\AcmeDemoBundle'],
            'path traversal' => ['../AcmeDemoBundle'],
            'nested path traversal' => ['../../etc/AcmeDemoBundle'],
            'absolute path' => ['/var/www/AcmeDemoBundle'],
            'trailing slash' => ['AcmeDemoBundle/'],
        ];
    }

    // ---------------------------------------------------------------------------------
    // Emtpy string - should throw
    // ---------------------------------------------------------------------------------

    #[Test]
    public function testThrowsForEmptyString(): void
    {
        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessage('Bundle name cannot be empty.');
        $this->resolver->resolve('');
    }

    // ---------------------------------------------------------------------------------
    // Exception message - should contain the offending name
    // ---------------------------------------------------------------------------------

    #[Test]
    public function testIncludesTheOffendingNameInTheExceptionMessage(): void
    {
        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessage('acme/AcmeDemoBundle');
        $this->resolver->resolve('acme/AcmeDemoBundle');
    }
}
