<?php

namespace Tourze\SymfonyLoggerBundle\Processor;

use Monolog\Processor\WebProcessor as BaseWebProcessor;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ResetInterface;

/**
 * 结束请求后，清空服务变量信息
 */
#[AutoconfigureTag(name: 'monolog.processor')]
#[AutoconfigureTag(name: 'as-coroutine')]
class WebEventSubscriber extends BaseWebProcessor implements EventSubscriberInterface, ResetInterface
{
    protected array $extraFields = [
        'url' => 'REQUEST_URI',
        'ip' => 'REMOTE_ADDR',
        'http_method' => 'REQUEST_METHOD',
        // 'server' => 'SERVER_NAME',
        // 'referrer' => 'HTTP_REFERER',
        // 'user_agent' => 'HTTP_USER_AGENT',
    ];

    public function __construct(?array $extraFields = null)
    {
        // Pass an empty array as the default null value would access $_SERVER
        parent::__construct([], $extraFields);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 4096],
            KernelEvents::TERMINATE => ['onTerminated', -4096],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($event->isMainRequest()) {
            $this->serverData = $event->getRequest()->server->all();
            $this->serverData['REMOTE_ADDR'] = $event->getRequest()->getClientIp();
        }
    }

    public function onTerminated(TerminateEvent $event): void
    {
        $this->reset();
    }

    public function reset(): void
    {
        $this->serverData = [];
    }
}
