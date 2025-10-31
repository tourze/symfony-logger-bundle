<?php

declare(strict_types=1);

namespace Tourze\SymfonyLoggerBundle\Processor;

use Symfony\Bridge\Monolog\Processor\AbstractTokenProcessor;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * 记录当前用户信息
 */
#[AutoconfigureTag(name: 'monolog.processor')]
class TokenProcessor extends AbstractTokenProcessor
{
    protected function getKey(): string
    {
        return 'token';
    }

    protected function getToken(): ?TokenInterface
    {
        try {
            return $this->tokenStorage->getToken();
        } catch (\Throwable) {
            return null;
        }
    }
}
