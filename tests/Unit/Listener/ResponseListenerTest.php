<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Listener;

use DvsaLogger\Listener\ResponseListener;
use DvsaLogger\Util\LoggerSpyTrait;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\PhpEnvironment\Response as PhpResponse;
use Laminas\Http\PhpEnvironment\Request as PhpRequest;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\ResponseInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ResponseListenerTest extends TestCase
{
    use LoggerSpyTrait;

    public function testLogResponseIgnoreNonPhpEnvironmentRequest(): void
    {
        $spy = $this->createLoggerSpy();

        $listener = new ResponseListener($spy);
        $event = new MvcEvent();
        $event->setRequest(new PhpRequest());

        $listener->logResponse($event);

        $this->assertFalse(
            $spy->debugCalled,
            'Expected debug to not be called for non-PhpEnvironment request',
        );
    }

    public function testLogResponseWithValidRequest(): void
    {
        $spy = $this->createLoggerSpy();

        $listener = new ResponseListener($spy);
        $request = new PhpRequest();
        $request->setUri('http://example.com/');

        $response = new PhpResponse();
        $response->setStatusCode(200)->setContent('OK');
        $response->getHeaders()
            ->addHeaderLine(
                'Content-Type',
                'application/json',
            );

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setResponse($response);

        $listener->logResponse($event);

        $this->assertSame(200, $spy->capturedContext['status_code']);
        $this->assertArrayHasKey('content_type', $spy->capturedContext);
        $this->assertArrayHasKey('response_content', $spy->capturedContext);
        $this->assertArrayHasKey('api_request_uuid', $spy->capturedContext);
        $this->assertArrayHasKey('frontend_request_uuid', $spy->capturedContext);
        $this->assertArrayHasKey('token', $spy->capturedContext);
        $this->assertArrayHasKey('execution_time', $spy->capturedContext);
    }

    public function testShutdownCallsCloseHandlers(): void
    {
        $spy = $this->createLoggerSpy();

        $listener = new ResponseListener($spy);
        $listener->shutdown();

        $this->assertTrue($spy->closedCalled);
    }

    public function testShutdownDoesNothingWhenMethodMissing(): void
    {
        $noCloseLogger = new class {
            public bool $closedCalled = false;
            public function debug(string $msg, array $context = []): void
            {
            }
        };

        $listener = new ResponseListener($noCloseLogger);
        $listener->shutdown();
        $this->assertFalse($noCloseLogger->closedCalled);
    }

    public function testAttachRegistersListeners(): void
    {
        $spy = $this->createLoggerSpy();
        $listener = new ResponseListener($spy);

        $events = $this->createMock(EventManagerInterface::class);
        $events->expects($this->exactly(2))
            ->method('attach')
            ->willReturn($events);

        $listener->attach($events);
    }

    public function testDetachRemovesAllListeners(): void
    {
        $spy = $this->createLoggerSpy();
        $listener = new ResponseListener($spy);

        $attachedListener = function (): void {
        };
        $events = $this->createMock(EventManagerInterface::class);
        $events->method('attach')->willReturn($attachedListener);
        $events->expects($this->exactly(1))
            ->method('detach');

        $listener->attach($events);
        $listener->detach($events);
    }

    public function testLogResponseWithMissingHeaders(): void
    {
        $spy = $this->createLoggerSpy();
        $listener = new ResponseListener($spy);
        $request = new PhpRequest();
        $request->setUri('https://example.com/');

        // No Authorization or X-request-uuid headers
        $response = new PhpResponse();
        $response->setStatusCode(201)->setContent('OK');

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setResponse($response);

        $listener->logResponse($event);

        $this->assertSame('', $spy->capturedContext['token']);
        $this->assertSame('', $spy->capturedContext['frontend_request_uuid']);
    }

    public function testLogResponseLoggerWithGetRequestUuid(): void
    {
        $logger = new class {
            public array $capturedContext = [];
            public function debug(string $msg, array $context = []): void
            {
                $this->capturedContext = $context;
            }
            public function getRequestUuid(): string
            {
                return 'uuid-123';
            }
        };

        $listener = new ResponseListener($logger);

        $request = new PhpRequest();
        $request->setUri('https://example.com/');

        $response = new PhpResponse();
        $response->setStatusCode(200)->setContent('OK');

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setResponse($response);

        $listener->logResponse($event);

        $this->assertSame('uuid-123', $logger->capturedContext['api_request_uuid']);
    }

    public function testLogResponseLoggerGetRequestUuidThrows(): void
    {
        $logger = new class {
            public array $capturedContext = [];
            public function debug(string $msg, array $context = []): void
            {
                $this->capturedContext = $context;
            }
            public function getRequestUuid(): string
            {
                throw new RuntimeException('fail');
            }
        };
        $listener = new ResponseListener($logger);

        $request = new PhpRequest();
        $request->setUri('https://example.com/');

        $response = new PhpResponse();
        $response->setStatusCode(200)->setContent('OK');

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setResponse($response);

        $listener->logResponse($event);

        $this->assertSame('', $logger->capturedContext['api_request_uuid']);
    }

    public function testLogResponseWithLargeContent(): void
    {
        $spy = $this->createLoggerSpy();
        $listener = new ResponseListener($spy);

        $request = new PhpRequest();
        $request->setUri('https://example.com/');

        $largeContent = str_repeat('A', 2000);

        $response = new PhpResponse();
        $response->setStatusCode(200)->setContent($largeContent);

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setResponse($response);

        $listener->logResponse($event);

        $this->assertSame(1000, strlen($spy->capturedContext['response_content']));
    }

    public function testLogResponseWithNoRequestTimeFloat(): void
    {
        $spy = $this->createLoggerSpy();
        $listener = new ResponseListener($spy);

        $request = new PhpRequest();
        $request->setUri('https://example.com/');

        $response = new PhpResponse();
        $response->setStatusCode(200)->setContent('OK');

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setResponse($response);

        $backup = $_SERVER['REQUEST_TIME_FLOAT'] ?? null;
        unset($_SERVER['REQUEST_TIME_FLOAT']);
        $listener->logResponse($event);

        if ($backup !== null) {
            $_SERVER['REQUEST_TIME_FLOAT'] = $backup;
        }

        $this->assertArrayHasKey('status_code', $spy->capturedContext);
    }

    public function testLogResponseWithNonResponse(): void
    {
        $spy = $this->createLoggerSpy();
        $listener = new ResponseListener($spy);

        $request = new PhpRequest();
        $request->setUri('https://example.com/');

        $event = new MvcEvent();
        $event->setRequest($request);
        $mockResponse = $this->getMockBuilder(ResponseInterface::class)
            ->getMock();
        $event->setResponse($mockResponse);

        $listener->logResponse($event);

        $this->assertFalse($spy->debugCalled);
    }
}
