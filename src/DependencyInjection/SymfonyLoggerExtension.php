<?php

namespace Tourze\SymfonyLoggerBundle\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

final class SymfonyLoggerExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../../config';
    }
}
