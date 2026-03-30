<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Listener;

use DvsaCommon\Exception\UnauthorisedException;
use DvsaCommon\HttpRestJson\Exception\ForbiddenOrUnauthorisedException;
use DvsaLogger\Contract\TokenServiceInterface;
use DvsaLogger\Listener\ExceptionListener;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

class ExceptionListenerTest extends TestCase
{
    private const EXCEPTION_MSG = 'Test exception';

    private array $originalEnv = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalEnv = [
            'TRACE_ID' => $_ENV['TRACE_ID'] ?? null,
            'SPAN_ID' => $_ENV['SPAN_ID'] ?? null,
            'PARENT_SPAN_ID' => $_ENV['PARENT_SPAN_ID'] ?? null,
        ];
        unset($_ENV['TRACE_ID'], $_ENV['SPAN_ID'], $_ENV['PARENT_SPAN_ID']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        foreach ($this->originalEnv as $key => $value) {
            if ($value === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $value;
            }
        }
    }

    public function testNoExceptionDoesNothing(): void
    {
        $spy = $this->createTestLogger();
        $listener = new ExceptionListener($spy);
        $listener->processException(new MvcEvent());

        $this->assertFalse(
            $spy->critCalled,
            'Expected crit to not be called when there is no exception',
        );
    }

    /**
     * @dataProvider skippedExceptionsProvider
     */
    public function testSkippedExceptionsDoNotLog(Throwable $exception): void
    {
        $spy = $this->createTestLogger();
        $listener = new ExceptionListener($spy);

        $event = new MvcEvent();
        $event->setParam('exception', $exception);

        $listener->processException($event);

        $this->assertFalse(
            $spy->critCalled,
            'Expected crit to not be called for skipped exceptions',
        );
    }

    public static function skippedExceptionsProvider(): array
    {
        return [
            'UnauthorisedException' => [
                new UnauthorisedException('Go away'),
            ],
            'ForbiddenOrUnauthorisedException' => [
                new ForbiddenOrUnauthorisedException('Forbidden'),
            ],
        ];
    }

    public function testServiceNotCreatedExceptionIsUnwrapped(): void
    {
        $logger = $this->createTestLogger();

        $inner = new RuntimeException('Real problem');
        $outer = new class ($inner) extends ServiceNotCreatedException {
            public function __construct(Throwable $prev)
            {
                parent::__construct('Wrapper', 0, $prev);
            }
        };

        $listener = new ExceptionListener($logger);
        $event = new MvcEvent();
        $event->setParam('exception', $outer);

        $listener->processException($event);

        $this->assertNotNull($logger->exception);
        $this->assertSame('Real problem', $logger->exception->getMessage());
    }

    public function testTraceIdSetFromEnv(): void
    {
        $logger = $this->createTestLogger();
        $_ENV['TRACE_ID'] = 'trace-abc';
        $_ENV['SPAN_ID'] = 'span-abc';
        $_ENV['PARENT_SPAN_ID'] = 'parent-span-abc';

        $listener = new ExceptionListener($logger);
        $event = new MvcEvent();
        $event->setParam('exception', new RuntimeException(self::EXCEPTION_MSG));

        $listener->processException($event);

        $this->assertSame('trace-abc', $logger->capturedTrace);
        $this->assertSame('span-abc', $logger->capturedSpan);
        $this->assertSame('parent-span-abc', $logger->capturedParentSpan);
    }

    /**
     * @dataProvider tokenServiceProvider
     */
    public function testTokenServiceScenarios(
        ?TokenServiceInterface $tokenService,
        ?string $expectedToken,
    ): void {
        $logger = $this->createTestLogger();

        $listener = new ExceptionListener($logger, $tokenService);

        $event = new MvcEvent();
        $event->setParam('exception', new RuntimeException(self::EXCEPTION_MSG));

        $listener->processException($event);

        $this->assertSame(
            $expectedToken ?? '',
            $logger->capturedToken,
            'Expected token to be empty before processing exception',
        );
    }

    public static function tokenServiceProvider(): array
    {
        return [
            'write_token_service' => [
                new class implements TokenServiceInterface {
                    public function getToken(): ?string
                    {
                        return 'test-token';
                    }
                },
                'test-token',
            ],
            'without_token_service' => [null, ''],
            'token_service_returns_empty_string' => [
                new class implements TokenServiceInterface {
                    public function getToken(): ?string
                    {
                        return '';
                    }
                },
                '',
            ],
        ];
    }

    public function testRegularExceptionLogsCritically(): void
    {
        $logger = $this->createTestLogger();
        $exception = new RuntimeException('Something went wrong');

        $listener = new ExceptionListener($logger);
        $event = new MvcEvent();
        $event->setParam('exception', $exception);

        $listener->processException($event);

        $this->assertSame('Something went wrong', $logger->msg);
        $this->assertSame($exception, $logger->exception);
    }

    public function testLoggerWithoutTraceMethodsStillLogs(): void
    {
        $logger = $this->createTestLogger();
        $exception = new RuntimeException('Something broke');

        $listener = new ExceptionListener($logger);
        $event = new MvcEvent();
        $event->setParam('exception', $exception);

        $listener->processException($event);

        $this->assertSame('Something broke', $logger->msg);
        $this->assertSame($exception, $logger->exception);
    }

    public function testAttachAndDetachListeners(): void
    {
        $logger = $this->createTestLogger();
        $listener = new ExceptionListener($logger);
        $mockEvents = $this->createMock(EventManagerInterface::class);
        $callable1 = function () {
        };
        $callable2 = function () {
        };
        $mockEvents->expects($this->exactly(2))
            ->method('attach')
            ->withConsecutive(
                [MvcEvent::EVENT_DISPATCH_ERROR, [$listener, 'processException'], PHP_INT_MAX],
                [MvcEvent::EVENT_RENDER_ERROR, [$listener, 'processException'], PHP_INT_MAX]
            )
            ->willReturnOnConsecutiveCalls($callable1, $callable2);
        $mockEvents->expects($this->exactly(2))
            ->method('detach')
            ->withConsecutive([$callable1], [$callable2])
            ->willReturn(true);
        $listener->attach($mockEvents);
        $listener->detach($mockEvents);
    }

    public function testInjectLoggerContextHandlesTokenServiceException(): void
    {
        $logger = $this->createTestLogger();
        $tokenService = new class implements TokenServiceInterface {
            public function getToken(): ?string
            {
                throw new RuntimeException('Token error');
            }
        };

        $listener = new ExceptionListener($logger, $tokenService);

        $event = new MvcEvent();
        $event->setParam('exception', new RuntimeException('fail'));

        $listener->processException($event);
        $this->assertSame('', $logger->capturedToken);
    }

    public function testGetEnvFallsBackToGetenv(): void
    {
        $logger = $this->createTestLogger();

        $listener = new class ($logger) extends ExceptionListener {
            public function callGetEnv(string $name): string
            {
                return parent::getEnv($name);
            }
        };

        putenv('TRACE_ID=from_getenv');
        unset($_ENV['TRACE_ID']);

        $this->assertSame('from_getenv', $listener->callGetEnv('TRACE_ID'));
        putenv('TRACE_ID'); // cleanup
    }

    public function testLoggerWithNoContextMethods(): void
    {
        $logger = new class {
            public bool $critCalled = false;
            public string $msg = '';
            public ?Throwable $exception = null;
            public function crit(string $msg, array $context = []): object
            {
                $this->critCalled = true;
                $this->msg = $msg;
                $this->exception = $context['ex'] ?? null;
                return $this;
            }
        };

        $listener = new ExceptionListener($logger);

        $event = new MvcEvent();
        $event->setParam('exception', new RuntimeException('no context'));

        $listener->processException($event);

        $this->assertTrue($logger->critCalled);
        $this->assertSame('no context', $logger->msg);
    }

    private function createTestLogger(): object
    {
        return new class {
            public bool $critCalled = false;
            public string $capturedTrace = '';
            public string $capturedSpan = '';
            public string $capturedParentSpan = '';
            public string $capturedToken = '';
            public string $msg = '';
            public ?Throwable $exception = null;

            public function crit(string $msg, array $context = []): object
            {
                $this->critCalled = true;
                $this->msg = $msg;
                $this->exception = $context['ex'] ?? null;
                return $this;
            }

            public function setTraceId(string $traceId): void
            {
                $this->capturedTrace = $traceId;
            }

            public function setSpanId(string $spanId): void
            {
                $this->capturedSpan = $spanId;
            }

            public function setParentSpanId(string $parentSpanId): void
            {
                $this->capturedParentSpan = $parentSpanId;
            }

            public function setToken(string $token): void
            {
                $this->capturedToken = $token;
            }
        };
    }
}
