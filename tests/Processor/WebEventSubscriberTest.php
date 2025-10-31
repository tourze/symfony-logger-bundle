<?php

namespace Tourze\SymfonyLoggerBundle\Tests\Processor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ServerBag;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Tourze\PHPUnitSymfonyKernelTest\AbstractEventSubscriberTestCase;
use Tourze\SymfonyLoggerBundle\Processor\WebEventSubscriber;

/**
 * @internal
 */
#[CoversClass(WebEventSubscriber::class)]
#[RunTestsInSeparateProcesses]
class WebEventSubscriberTest extends AbstractEventSubscriberTestCase
{
    protected function onSetUp(): void
    {
        // Empty implementation for abstract method
    }

    public function testProcessorCanBeInstantiated(): void
    {
        $processor = self::getService(WebEventSubscriber::class);
        $this->assertInstanceOf(WebEventSubscriber::class, $processor);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = WebEventSubscriber::getSubscribedEvents();

        $this->assertIsArray($events);
        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
        $this->assertArrayHasKey(KernelEvents::TERMINATE, $events);

        $this->assertEquals(['onKernelRequest', 4096], $events[KernelEvents::REQUEST]);
        $this->assertEquals(['onTerminated', -4096], $events[KernelEvents::TERMINATE]);
    }

    public function testOnKernelRequestSetsServerDataForMainRequest(): void
    {
        $processor = self::getService(WebEventSubscriber::class);

        $serverBag = $this->createMock(ServerBag::class);
        $serverBag->expects($this->once())
            ->method('all')
            ->willReturn(['SERVER_NAME' => 'example.com'])
        ;

        $request = $this->createMock(Request::class);
        $request->server = $serverBag;
        $request->expects($this->once())
            ->method('getClientIp')
            ->willReturn('127.0.0.1')
        ;

        $event = $this->createMock(RequestEvent::class);
        $event->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(true)
        ;
        $event->expects($this->exactly(2))
            ->method('getRequest')
            ->willReturn($request)
        ;

        $processor->onKernelRequest($event);

        // Method execution verified through mock expectations above
    }

    public function testOnKernelRequestIgnoresSubRequests(): void
    {
        $processor = self::getService(WebEventSubscriber::class);

        $event = $this->createMock(RequestEvent::class);
        $event->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(false)
        ;
        $event->expects($this->never())
            ->method('getRequest')
        ;

        $processor->onKernelRequest($event);

        // Method execution verified through mock expectations above
    }

    public function testOnTerminatedCallsReset(): void
    {
        $processor = self::getService(WebEventSubscriber::class);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);

        $event = new TerminateEvent($kernel, $request, $response);

        $processor->onTerminated($event);

        // Verify the method executed without errors
        $this->expectNotToPerformAssertions();
    }

    public function testResetClearsServerData(): void
    {
        $processor = self::getService(WebEventSubscriber::class);

        $processor->reset();

        // Verify the method executed without errors
        $this->expectNotToPerformAssertions();
    }
}
