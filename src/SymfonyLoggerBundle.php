<?php

namespace Tourze\SymfonyLoggerBundle;

use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;

class SymfonyLoggerBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            MonologBundle::class => ['all' => true],
        ];
    }
}
