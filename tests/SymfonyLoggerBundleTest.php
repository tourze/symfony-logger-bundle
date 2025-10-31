<?php

namespace Tourze\SymfonyLoggerBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\SymfonyLoggerBundle\SymfonyLoggerBundle;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(SymfonyLoggerBundle::class)]
class SymfonyLoggerBundleTest extends AbstractBundleTestCase
{
    public function testBundleDependencies(): void
    {
        $dependencies = SymfonyLoggerBundle::getBundleDependencies();

        $this->assertIsArray($dependencies);
        $this->assertArrayHasKey(MonologBundle::class, $dependencies);
        $this->assertEquals(['all' => true], $dependencies[MonologBundle::class]);
    }

    // Bundle instantiation test is handled by the base class
}
