<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Listener;

use DvsaLogger\Listener\ApiClientRequestListener;
use DvsaLogger\Logger\MotLogger;
use DvsaLogger\Util\LoggerSpyTrait;
use Laminas\EventManager\Event;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\MvcEvent;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

class ApiClientRequestListenerTest extends TestCase
{
    use LoggerSpyTrait;

    public function testLogStartOfRequest(): void
    {
        $spy = $this->createLoggerSpy();

        $listener = new ApiClientRequestListener($spy);

        $event = new Event('startOfRequest');
        $event->setParam('resourcePath', '/api/users');
        $event->setParam('request_method', 'GET');
        $event->setParam('content', '');

        $listener->logStartOfRequest($event);

        $this->assertTrue($spy->debugCalled, 'Expected debug method to be called on the logger');
        $this->assertArrayHasKey('endpoint_uri', $spy->capturedContext);
        $this->assertArrayHasKey('request_method', $spy->capturedContext);
        $this->assertArrayHasKey('parameters', $spy->capturedContext);
        $this->assertArrayHasKey('request_uuid', $spy->capturedContext);
        $this->assertSame('/api/users', $spy->capturedContext['endpoint_uri']);
        $this->assertSame('GET', $spy->capturedContext['request_method']);
    }

    public function testLogStartOfRequestIncludesRequestUuidFromLogger(): void
    {
        $spy = $this->createLoggerSpy('client-uuid');

        $listener = new ApiClientRequestListener($spy);

        $event = new Event('startOfRequest');
        $event->setParam('resourcePath', '/api/test');
        $event->setParam('request_method', 'POST');

        $listener->logStartOfRequest($event);

        $this->assertSame('client-uuid', $spy->capturedContext['request_uuid']);
    }

    public function testLogStartOfRequestWithEmptyParams(): void
    {
        $spy = $this->createLoggerSpy();

        $listener = new ApiClientRequestListener($spy);

        $event = new Event('startOfRequest');

        $listener->logStartOfRequest($event);

        $this->assertTrue($spy->debugCalled, 'Expected debug method to be called on the logger');
        $this->assertSame('', $spy->capturedContext['endpoint_uri']);
        $this->assertSame('', $spy->capturedContext['request_method']);
    }

    public function testAttachRegistersListeners(): void
    {
        $spy = $this->createLoggerSpy();
        $listener = new ApiClientRequestListener($spy);

        $events = $this->createMock(EventManager::class);
        $sharedManager = $this->createMock(SharedEventManagerInterface::class);
        $sharedManager->expects($this->once())
            ->method('attach')
            ->with('DvsaCommon\HttpRestJson\Client', 'startOfRequest')
            ->willReturn($sharedManager);
        $events->method('getSharedManager')->willReturn($sharedManager);

        $listener->attach($events);
    }

    public function testDetachRemovesAllListeners(): void
    {
        $spy = $this->createLoggerSpy();
        $listener = new ApiClientRequestListener($spy);

        $events = $this->createMock(EventManager::class);
        $sharedManager = $this->createMock(SharedEventManagerInterface::class);
        $attachedListener = function (): void {
        };
        $sharedManager->method('attach')->willReturn($attachedListener);
        $sharedManager->expects($this->once())
            ->method('detach')
            ->with($attachedListener);
        $events->method('getSharedManager')->willReturn($sharedManager);

        $listener->attach($events);
        $listener->detach($events);

        $this->assertEmpty($this->getListeners($listener));
    }

    public function testLogStartOfRequestHandlesExceptionFromGetRequestUuid(): void
    {
        $logger = $this->createMock(MotLogger::class);
        $logger->expects($this->once())
            ->method('getRequestUuid')
            ->willThrowException(new RuntimeException('fail'));
        $logger->expects($this->once())
            ->method('debug')
            ->with(
                '',
                $this->callback(function ($context) {
                    // request_uuid should be empty string if exception is thrown
                    return isset($context['request_uuid']) && $context['request_uuid'] === '';
                })
            );

        $event = $this->createMock(MvcEvent::class);
        $event->method('getParam')->willReturnMap([
            ['resourcePath', '', '/api/test'],
            ['request_method', '', 'POST'],
            ['content', '', ['foo' => 'bar']],
        ]);

        $listener = new ApiClientRequestListener($logger);
        $listener->logStartOfRequest($event);
    }

    private function getListeners(ApiClientRequestListener $listener): array
    {
        $ref = new ReflectionClass($listener);
        $prop = $ref->getProperty('listeners');
        $prop->setAccessible(true);
        return $prop->getValue($listener);
    }
}
