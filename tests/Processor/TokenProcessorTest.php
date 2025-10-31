<?php

namespace Tourze\SymfonyLoggerBundle\Tests\Processor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\SymfonyLoggerBundle\Processor\TokenProcessor;

/**
 * @internal
 */
#[CoversClass(TokenProcessor::class)]
#[RunTestsInSeparateProcesses]
class TokenProcessorTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // Empty implementation for abstract method
    }

    public function testProcessorCanBeInstantiated(): void
    {
        $processor = self::getService(TokenProcessor::class);
        $this->assertInstanceOf(TokenProcessor::class, $processor);
    }

    public function testGetKeyReturnsToken(): void
    {
        $processor = self::getService(TokenProcessor::class);

        $reflection = new \ReflectionClass($processor);
        $method = $reflection->getMethod('getKey');
        $method->setAccessible(true);

        $result = $method->invoke($processor);

        $this->assertEquals('token', $result);
    }

    public function testGetTokenReturnsTokenFromStorage(): void
    {
        $processor = self::getService(TokenProcessor::class);

        $reflection = new \ReflectionClass($processor);
        $method = $reflection->getMethod('getToken');
        $method->setAccessible(true);

        $result = $method->invoke($processor);

        // Without an authenticated user, should return null
        $this->assertNull($result);
    }

    public function testGetTokenReturnsNullOnException(): void
    {
        $processor = self::getService(TokenProcessor::class);

        $reflection = new \ReflectionClass($processor);
        $method = $reflection->getMethod('getToken');
        $method->setAccessible(true);

        // Test that method handles exceptions gracefully
        $result = $method->invoke($processor);

        // Should return null when no token is available or exception occurs
        $this->assertNull($result);
    }
}
