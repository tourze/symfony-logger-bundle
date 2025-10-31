<?php

namespace Tourze\SymfonyLoggerBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\SymfonyLoggerBundle\DependencyInjection\SymfonyLoggerExtension;

/**
 * @internal
 */
#[CoversClass(SymfonyLoggerExtension::class)]
class SymfonyLoggerExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    // All tests are inherited from the base class
    // testExtendsCorrectBaseClass, testLoadShouldRegisterServices, etc. are automatically provided
}
