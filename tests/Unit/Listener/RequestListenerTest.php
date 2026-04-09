<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Listener;

use DvsaLogger\Listener\RequestListener;
use DvsaLogger\Util\LoggerSpyTrait;
use DvsaLogger\Util\MvcEventTrait;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Header\UserAgent;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\PhpEnvironment\Request as PhpRequest;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\Stdlib\RequestInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RequestListenerTest extends TestCase
{
    use LoggerSpyTrait;
    use MvcEventTrait;

    public function testLogRequestIgnoresNonPhpEnvironmentRequest(): void
    {
        $spy = $this->createLoggerSpy();
        $listener = new RequestListener($spy);

        $event = new MvcEvent();
        $event->setRequest(new class implements RequestInterface {
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
        });
        ;

        $listener->logRequest($event);
        $this->assertFalse(
            $spy->debugCalled,
            'Expected debug to not be called for non-PhpEnvironment request',
        );
    }

    public function testLogRequestWithValidRequest(): void
    {
        $spy = $this->createLoggerSpy();

        $listener = new RequestListener($spy);

        $request = new PhpRequest();
        $request->setUri('https://example.com/');
        $request->setMethod('GET');

        $routeMatch = new RouteMatch([
            'controller' => 'TestController',
            'action' => 'index',
        ]);

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setRouteMatch($routeMatch);

        $listener->logRequest($event);

        $this->assertTrue(
            $spy->debugCalled,
            'Expected debug to not be called for non-PhpEnvironment request',
        );
        $this->assertArrayHasKey('uri', $spy->capturedContext);
        $this->assertArrayHasKey('request_method', $spy->capturedContext);
        $this->assertArrayHasKey('ip_address', $spy->capturedContext);
        $this->assertArrayHasKey('route', $spy->capturedContext);
        $this->assertArrayHasKey('parameters', $spy->capturedContext);
    }

    public function testLogRequestWithoutRouteMatch(): void
    {
        $spy = $this->createLoggerSpy();

        $listener = new RequestListener($spy);

        $request = new PhpRequest();
        $request->setUri('https://example.com/');
        $event = new MvcEvent();
        $event->setRequest($request);

        $listener->logRequest($event);

        $this->assertSame('', $spy->capturedContext['route']);
    }

    public function testLogRequestIncludesUsernameFromLogger(): void
    {
        $spy = $this->createLoggerSpy();

        $listener = new RequestListener($spy);
        $request = new Request();
        $request->setUri('https://example.com/');

        $event = new MvcEvent();
        $event->setRequest($request);

        $listener->logRequest($event);

        $this->assertSame('test-user', $spy->capturedContext['username']);
    }

    public function testLogRequestIncludesRequestUuidFromLogger(): void
    {
        $spy = $this->createLoggerSpy('test-uuid-123');

        $listener = new RequestListener($spy);
        $request = new Request();
        $request->setUri('https://example.com/');

        $event = new MvcEvent();
        $event->setRequest($request);

        $listener->logRequest($event);

        $this->assertSame('test-uuid-123', $spy->capturedContext['request_uuid']);
    }

    public function testAttachAndDetach(): void
    {
        $spy = $this->createLoggerSpy();
        $listener = new RequestListener($spy);

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

    public function testLogRequestWithUserAgentHeader(): void
    {
        $spy = $this->createLoggerSpy();
        $listener = new RequestListener($spy);

        $request = new PhpRequest();
        $request->setUri('https://example.com/');
        $request->setMethod('GET');

        $userAgent = new UserAgent('TestAgent/1.0');
        $request->getHeaders()->addHeader($userAgent);

        $event = new MvcEvent();
        $event->setRequest($request);

        $listener->logRequest($event);

        $this->assertSame('TestAgent/1.0', $spy->capturedContext['user_agent']);
    }

    public function testLogRequestWithNoUserAgentHeader(): void
    {
        $spy = $this->createLoggerSpy();
        $listener = new RequestListener($spy);

        $request = new PhpRequest();
        $request->setUri('https://example.com/');
        $request->setMethod('GET');

        $event = new MvcEvent();
        $event->setRequest($request);

        $listener->logRequest($event);

        $this->assertSame('', $spy->capturedContext['user_agent']);
    }

    public function testLogRequestWithNonUserAgentHeader(): void
    {
        $spy = $this->createLoggerSpy();
        $listener = new RequestListener($spy);

        $request = new PhpRequest();
        $request->setUri('https://example.com/');
        $request->setMethod('GET');
        $request->getHeaders()
            ->addHeaderLine(
                'UserAgent',
                'not-a-useragent-object',
            );

        $event = new MvcEvent();
        $event->setRequest($request);

        $listener->logRequest($event);

        $this->assertSame('', $spy->capturedContext['user_agent']);
    }

    public function testLogRequestHandlesLoggerGetBasicMetadataThrows(): void
    {
        $logger = new class {
            public function debug($msg, $context = [])
            {
            }
            public function getBasicMetadata($level)
            {
                throw new RuntimeException('fail');
            }
        };

        $listener = new RequestListener($logger);

        $request = new PhpRequest();
        $request->setUri('https://example.com/');

        $event = new MvcEvent();
        $event->setRequest($request);

        $listener->logRequest($event);

        // Should not throw, username should be ''
        $this->assertTrue(true);
    }

    public function testLogRequestHandlesLoggerGetRequestUuidThrows(): void
    {
        $logger = new class {
            public function debug($msg, $context = [])
            {
            }
            public function getRequestUuid()
            {
                throw new RuntimeException('fail');
            }
        };

        $listener = new RequestListener($logger);

        $request = new PhpRequest();
        $request->setUri('https://example.com/');

        $event = new MvcEvent();
        $event->setRequest($request);

        $listener->logRequest($event);

        // Should not throw, request_uuid should be ''
        $this->assertTrue(true);
    }

    public function testLogRequestHandlesLoggerWithoutGetBasicMetadataOrGetRequestUuid(): void
    {
        $logger = new class {
            public function debug($msg, $context = [])
            {
            }
        };

        $listener = new RequestListener($logger);

        $request = new PhpRequest();
        $request->setUri('https://example.com/');

        $event = new MvcEvent();
        $event->setRequest($request);

        $listener->logRequest($event);

        // Should not throw, username and request_uuid should be ''
        $this->assertTrue(true);
    }

    public function testLogRequestIncludesSessionIdAndMemoryUsage(): void
    {
        $spy = $this->createLoggerSpy();

        $listener = new RequestListener($spy);

        $request = new PhpRequest();
        $request->setUri('https://example.com/');

        $event = new MvcEvent();
        $event->setRequest($request);

        $listener->logRequest($event);

        $this->assertArrayHasKey('php_session_id', $spy->capturedContext);
        $this->assertArrayHasKey('memory_usage', $spy->capturedContext);
    }

    public function testLogRequestParametersStructure(): void
    {
        $spy = $this->createLoggerSpy();

        $listener = new RequestListener($spy);

        $request = new PhpRequest();
        $request->setUri('https://example.com/');
        $request->setMethod('POST');
        $request->getQuery()->fromArray(['foo' => 'bar']);
        $request->setContent('body-content');

        $event = new MvcEvent();
        $event->setRequest($request);

        $listener->logRequest($event);
        $params = $spy->capturedContext['parameters'];

        $this->assertSame(['foo' => 'bar'], $params['get_vars']);
        $this->assertSame('body-content', $params['post_vars']);
        $this->assertIsArray($params['route']);
    }
}
