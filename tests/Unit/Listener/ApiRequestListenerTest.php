<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Listener;

use DvsaLogger\Listener\ApiRequestListener;
use DvsaLogger\Util\LoggerSpyTrait;
use DvsaLogger\Util\MvcEventTrait;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\RequestInterface;
use PHPUnit\Framework\TestCase;
use Random\RandomException;
use RuntimeException;
use stdClass;

class ApiRequestListenerTest extends TestCase
{
    use LoggerSpyTrait;
    use MvcEventTrait;

    /**
     * @throws RandomException
     */
    public function testLogRequestIgnoresNonPhpEnvironmentRequest(): void
    {
        $spy = $this->createLoggerSpy();

        $listener = new ApiRequestListener($spy);
        $listener->logRequest($this->createEvent(
            request: new class implements RequestInterface {
                public function getUri(): object
                {
                    return new class {
                        public function getUriString(): string
                        {
                            return 'https://example.com/';
                        }
                    };
                }
                public function getMethod(): string
                {
                    return 'GET';
                }
                public function setMetadata($spec, $value = null)
                {
                    return $this;
                }
                public function getMetadata($key = null): null
                {
                    return null;
                }
                public function setContent($content)
                {
                    return $this;
                }
                public function getContent(): null
                {
                    return null;
                }
            }
        ));

        $this->assertFalse($spy->debugCalled);
    }

    /**
     * @throws RandomException
     */
    public function testLogRequestWithValidRequest(): void
    {
        $spy = $this->createLoggerSpy();
        $listener = new ApiRequestListener($spy);

        $listener->logRequest($this->createEvent([
            'method' => 'POST',
            'query' => ['page' => '1'],
            'content' => '{"name":"test"}',
        ]));

        $this->assertTrue($spy->debugCalled);
        $context = $spy->capturedContext;

        foreach (
            [
            'api_request_uuid',
            'uri',
            'request_method',
            'parameters',
            'token',
            'frontend_request_uuid',
            'ip_address',
            'user_agent',
            ] as $key
        ) {
            $this->assertArrayHasKey($key, $context, "Missing context key: $key");
        }

        $this->assertSame('http://example.com/api/test', $context['uri']);
        $this->assertSame('POST', $context['request_method']);
        $this->assertIsArray($context['parameters']);
        $this->assertSame(['page' => '1'], $context['parameters']['get_vars']);
        $this->assertSame('{"name":"test"}', $context['parameters']['post_vars']);
        $this->assertSame('', $context['token']);
        $this->assertSame('', $context['frontend_request_uuid']);
        $this->assertSame('', $context['user_agent']);
        $this->assertIsString($context['ip_address']);
    }

    /**
     * @throws RandomException
     */
    public function testLogRequestExtractAuthorizationHeader(): void
    {
        $spy = $this->createLoggerSpy();
        $listener = new ApiRequestListener($spy);

        $listener->logRequest($this->createEvent([
            'headers' => ['Authorization' => 'Bearer foo'],
        ]));

        $this->assertStringContainsString('Bearer foo', $spy->capturedContext['token']);
    }

    /**
     * @throws RandomException
     */
    public function testLogRequestUserRequestUuidFromLogger(): void
    {
        $spy = $this->createLoggerSpy('logger-uuid-123');
        $listener = new ApiRequestListener($spy);

        $listener->logRequest($this->createEvent());

        $this->assertSame('logger-uuid-123', $spy->capturedContext['api_request_uuid']);
    }

    /**
     * @throws RandomException
     */
    public function testLogRequestGeneratesUuidWhenNotAvailable(): void
    {
        $spy = $this->createLoggerSpy();
        $listener = new ApiRequestListener($spy);

        $listener->logRequest($this->createEvent());

        $this->assertNotEmpty($spy->capturedContext['api_request_uuid']);
        $this->assertIsString($spy->capturedContext['api_request_uuid']);
    }

    public function testAttachAndDetach(): void
    {
        $logger = $this->createLoggerSpy();
        $listener = new ApiRequestListener($logger);

        $events = $this->getMockBuilder(EventManagerInterface::class)
            ->onlyMethods(['attach', 'detach'])
            ->getMockForAbstractClass();

        $callable = [$listener, 'logRequest'];
        $events->expects($this->once())
            ->method('attach')
            ->with(
                MvcEvent::EVENT_ROUTE,
                $callable,
                1
            )
            ->willReturn($callable);

        $events->expects($this->once())
            ->method('detach')
            ->with($callable)
            ->willReturn(true);

        $listener->attach($events);
        $listener->detach($events);
    }

    /**
     * @throws RandomException
     */
    public function testLogRequestHandlesLoggerWithoutGetRequestUuid(): void
    {
        $spy = $this->createLoggerSpy();
        $listener = new ApiRequestListener($spy);

        // Remove getRequestUuid method if present
        unset($spy->getRequestUuid);

        $listener->logRequest($this->createEvent());

        $this->assertNotEmpty($spy->capturedContext['api_request_uuid']);
    }

    /**
     * @throws RandomException
     */
    public function testLogRequestHandlesLoggerGetRequestUuidThrows(): void
    {
        $spy = new class extends stdClass {
            public bool $debugCalled = false;
            public array $capturedContext = [];
            public function debug($message, array $context = []): void
            {
                $this->debugCalled = true;
                $this->capturedContext = $context;
            }
            public function getRequestUuid(): string
            {
                throw new RuntimeException('fail');
            }
        };

        $listener = new ApiRequestListener($spy);
        $listener->logRequest($this->createEvent());

        $this->assertNotEmpty($spy->capturedContext['api_request_uuid']);
    }

    /**
     * @throws RandomException
     */
    public function testLogRequestHandlesNonAuthorizationHeader(): void
    {
        $spy = $this->createLoggerSpy();
        $listener = new ApiRequestListener($spy);

        $event = $this->createEvent([
            'headers' => [
                'Authorization' => 'not-bearer'
            ]
        ]);

        $listener->logRequest($event);

        $this->assertSame('not-bearer', $spy->capturedContext['token']);
    }

    /**
     * @throws RandomException
     */
    public function testLogRequestHandlesNonGenericHeaderForUuid(): void
    {
        $spy = $this->createLoggerSpy();
        $listener = new ApiRequestListener($spy);

        $event = $this->createEvent([
            'headers' => [
                'X-request-uuid' => 'not-uuid'
            ]
        ]);

        $listener->logRequest($event);

        $this->assertSame('not-uuid', $spy->capturedContext['frontend_request_uuid']);
    }

    /**
     * @throws RandomException
     */
    public function testLogRequestHandlesNonUserAgentHeader(): void
    {
        $spy = $this->createLoggerSpy();
        $listener = new ApiRequestListener($spy);

        $event = $this->createEvent([
            'headers' => [
                'UserAgent' => 'not-ua'
            ]
        ]);

        $listener->logRequest($event);

        $this->assertSame('', $spy->capturedContext['user_agent']);
    }
}
