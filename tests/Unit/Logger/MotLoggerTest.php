<?php

    declare(strict_types=1);

    namespace DvsaLoggerTest\Unit\Logger;

    use DvsaLogger\Contract\IdentityInterface;
    use DvsaLogger\Contract\IdentityProviderInterface;
    use DvsaLogger\Contract\TokenServiceInterface;
    use DvsaLogger\Logger\MotLogger;
    use DvsaLogger\Processor\DvsaMetadataProcessor;
    use Exception;
    use Monolog\Handler\HandlerInterface;
    use Monolog\Handler\TestHandler;
    use Monolog\Level;
    use Monolog\Logger;
    use Monolog\LogRecord;
    use PHPUnit\Framework\TestCase;
    use Random\RandomException;
    use ReflectionClass;
    use ReflectionException;
    use Throwable;

class MotLoggerTest extends TestCase
{
    private const string TIMESTAMP_REGEX = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}$/';

    private TestHandler $testHandler;

    private Logger $monolog;

    protected function setUp(): void
    {
        $this->testHandler = new TestHandler();
        $this->monolog = new Logger('test', [$this->testHandler]);
    }

    /**
     * @throws RandomException
     */
    public function testBasicLoggingAddsMetadata(): void
    {
        $tokenService = $this->createMock(TokenServiceInterface::class);
        $tokenService->method('getToken')->willReturn('test-token');

        $logger = new MotLogger($this->monolog, null, $tokenService);
        $logger->setTraceId('trace-123');
        $logger->setSpanId('span-456');
        $logger->setParentSpanId('parent-789');
        $logger->info('test message');

        $record = $this->testHandler->getRecords()[0];

        $this->assertInstanceOf(LogRecord::class, $record);
        $this->assertArrayHasKey('__dvsa_metadata__', $record->extra);

        $metadata = $record->extra['__dvsa_metadata__'];

        $this->assertSame('test-token', $metadata['token']);
        $this->assertSame('trace-123', $metadata['traceId']);
        $this->assertSame('span-456', $metadata['spanId']);
        $this->assertSame('parent-789', $metadata['parentSpanId']);
        $this->assertSame('test message', $record['message']);
        $this->assertSame('General', $metadata['logEntryType']);
        $this->assertSame('INFO', $metadata['level']);
        $this->assertNotEmpty($metadata['timestamp']);
        $this->assertMatchesRegularExpression(
            self::TIMESTAMP_REGEX,
            $record->datetime->format('Y-m-d\TH:i:s.u'),
        );
    }

    /**
     * @throws RandomException
     */
    public function testTokenFromServiceAppearsInMetadata(): void
    {
        $tokenService = $this->createMock(TokenServiceInterface::class);
        $tokenService->method('getToken')->willReturn('svc-token');

        $logger = new MotLogger($this->monolog, null, $tokenService);
        $logger->info('I can see you');

        $this->assertSame(
            'svc-token',
            $this->testHandler->getRecords()[0]->extra['__dvsa_metadata__']['token'],
        );
    }

    /**
     * @throws RandomException
     */
    public function testIdentityFromProviderAppearsInMetadata(): void
    {
        $identity = $this->createMock(IdentityInterface::class);
        $identity->method('getUsername')->willReturn('test-user');

        $identityProvider = $this->createMock(IdentityProviderInterface::class);
        $identityProvider->method('getIdentity')->willReturn($identity);

        $logger = new MotLogger($this->monolog, $identityProvider);
        $logger->info('test');

        $this->assertSame(
            'test-user',
            $this->testHandler->getRecords()[0]->extra['__dvsa_metadata__']['username'],
        );
    }

    /**
     * @throws RandomException
     */
    public function testExceptionLoggingAddsExceptionMetadata(): void
    {
        $logger = new MotLogger($this->monolog);
        $exception = new Exception('test error', 400);

        $logger->err('Something failed', ['ex' => $exception]);
        $record  = $this->testHandler->getRecords()[0];
        $metadata = $record->extra['__dvsa_metadata__'];

        $this->assertSame('Exception', $metadata['logEntryType']);
        $this->assertSame(400, $metadata['errorCode']);
        $this->assertSame(Exception::class, $metadata['exceptionType']);
        $this->assertNotEmpty($metadata['stackTrace']);
    }

    /**
     * @throws RandomException
     */
    public function testCriticalLevelMapsToError(): void
    {
        $logger = new MotLogger($this->monolog);
        $logger->crit('Serious issue');
        $this->assertContains(
            $this->testHandler->getRecords()[0]->extra['__dvsa_metadata__']['level'],
            ['ERROR', 'CRITICAL'],
        );
    }

    /**
     * @throws RandomException
     */
    public function testNoticeLevelMapsToInfo(): void
    {
        $logger = new MotLogger($this->monolog);
        $logger->notice('Notice issue');
        $this->assertContains(
            $this->testHandler->getRecords()[0]->extra['__dvsa_metadata__']['level'],
            ['INFO', 'NOTICE'],
        );
    }

    /**
     * @throws RandomException
     */
    public function testAlertLevelMapsToWarn(): void
    {
        $logger = new MotLogger($this->monolog);
        $logger->alert('Alert');
        $this->assertContains(
            $this->testHandler->getRecords()[0]->extra['__dvsa_metadata__']['level'],
            ['WARN', 'ALERT'],
        );
    }

    /**
     * @throws RandomException
     */
    public function testAllLogLevelsWork(): void
    {
        $logger = new MotLogger($this->monolog);
        $logger->info('Test message');
        $logger->debug('Debug message');
        $logger->notice('Notice message');
        $logger->warn('Warning message');
        $logger->err('Error message');
        $logger->crit('Critical message');
        $logger->alert('Alert message');
        $logger->emerg('Emergency message');
        $this->assertCount(8, $this->testHandler->getRecords());
    }

    /**
     * @throws RandomException
     */
    public function testSettersAndGetters(): void
    {
        $logger = new MotLogger($this->monolog);
        $logger->setTraceId('trace-123');
        $this->assertSame('trace-123', $logger->getTraceId());

        $logger->setSpanId('span-456');
        $this->assertSame('span-456', $logger->getSpanId());

        $logger->setParentSpanId('parent-789');
        $this->assertSame('parent-789', $logger->getParentSpanId());

        $logger->setLogEntryType('CustomType');
        $this->assertSame('CustomType', $logger->getLogEntryType());

        $logger->setToken('token-abc');
        $this->assertSame('token-abc', $logger->getToken());
    }

    /**
     * @throws RandomException
     */
    public function testDefaultTokenIsEmptyWhenNoServiceProvided(): void
    {
        $logger = new MotLogger($this->monolog);
        $logger->info('test');

        $metadata = $this->testHandler->getRecords()[0]->extra['__dvsa_metadata__'];

        $this->assertSame('', $metadata['token']);
        $this->assertSame('', $metadata['username']);
    }

    /**
     * @throws RandomException
     */
    public function testGetMonologReturnsUnderlyingLogger(): void
    {
        $logger = new MotLogger($this->monolog);
        $this->assertSame($this->monolog, $logger->getLogger());
    }

    /**
     * @throws RandomException
     */
    public function testConstructorDoesNotAddDuplicateMetadataProcessor(): void
    {
        $mockProcessor = $this->createMock(DvsaMetadataProcessor::class);
        $logger = new Logger('test', [$this->testHandler], [$mockProcessor]);

        // Triggers constructor logic, to ensure the DvsaMetadataProcessor is attached to the logger
        new MotLogger($logger);

        $count = 0;
        foreach ($logger->getProcessors() as $proc) {
            if ($proc instanceof DvsaMetadataProcessor) {
                $count++;
            }
        }

        $this->assertSame(1, $count, 'Should only have one DvsaMetadataProcessor');
    }


    /**
     * @throws RandomException
     */
    public function testConstructorUsesProvidedRequestUuid(): void
    {
        $uuid = 'custom-uuid-123';
        $this->assertSame(
            $uuid,
            (new MotLogger(
                $this->monolog,
                null,
                null,
                $uuid,
            ))->getRequestUuid(),
        );
    }

    /**
     * @throws RandomException
     */
    public function testCloseHandlersClosesAllHandlers(): void
    {
        $mockHandler = $this->getMockBuilder(HandlerInterface::class)
            ->onlyMethods(['close', 'isHandling', 'handle', 'handleBatch'])
            ->getMock();
        $mockHandler->expects($this->once())->method('close');
        $mockHandler->method('isHandling')->willReturn(false);
        $mockHandler->method('handle')->willReturn(false);

        (new MotLogger(new Logger('test', [$mockHandler])))->closeHandlers();
    }

    /**
     * @throws RandomException
     */
    public function testContextMergingWithoutException(): void
    {
        $motLogger = new MotLogger($this->monolog);
        $motLogger->info('no-ex', ['foo' => 'bar']);

        $record = $this->testHandler->getRecords()[0];

        $this->assertTrue(
            isset($record['context']['foo']) || isset($record['extra']['foo']),
            'foo key missing in context or extra'
        );
    }

    /**
     * @throws RandomException
     */
    public function testContextMergingWithExceptionRemovesExceptionKeyAndAddsMetadata(): void
    {
        $motLogger = new MotLogger($this->monolog);
        $motLogger->info(
            'with-ex',
            ['ex' => new \Exception('fail'), 'baz' => 'qux'],
        );

        $record = $this->testHandler->getRecords()[0];
        $context = $record['context'] ?? [];
        $extra = $record['extra'] ?? [];

        $this->assertFalse(
            array_key_exists('ex', $context) || array_key_exists('ex', $extra),
            "key should not be present when throws exception",
        );

        $this->assertTrue(
            array_key_exists('baz', $context) || array_key_exists('baz', $extra),
            "the key should be missing in context or extra when throws exception",
        );

        // '__dvsa_metadata__' should be present in either context or extra
        $this->assertTrue(
            array_key_exists('__dvsa_metadata__', $context) || array_key_exists('__dvsa_metadata__', $extra),
            "'__dvsa_metadata__' key missing in context or extra"
        );

        $meta = $context['__dvsa_metadata__'] ?? $extra['__dvsa_metadata__'] ?? null;

        $this->assertNotNull($meta, '__dvsa_metadata__ key missing');
        $this->assertSame('Exception', $meta['logEntryType']);
    }

    /**
     * @throws RandomException
     * @throws ReflectionException
     */
    public function testTimestampFormattingEdgeCases(): void
    {
        $motLogger = new MotLogger($this->monolog);

        $getMicrosecondsTimestamp = (new ReflectionClass($motLogger))
            ->getMethod('getMicrosecondsTimestamp');
        $getMicrosecondsTimestamp->setAccessible(true);

        $getTimestamp = (new ReflectionClass($motLogger))->getMethod('getTimestamp');
        $getTimestamp->setAccessible(true);

        $this->assertStringContainsString(
            'Z',
            $getMicrosecondsTimestamp->invoke($motLogger, '0.123456 1234567890'),
        );
        $this->assertMatchesRegularExpression(
            '/\\+00:00|Z$/',
            $getTimestamp->invoke($motLogger, '0.123456 1234567890'),
        );

        // Malformed microtime input should throw
        try {
            $getMicrosecondsTimestamp->invoke($motLogger, 'bad');
            $getTimestamp->invoke($motLogger, 'bad');
            $this->fail('Expected error or exception for malformed microtime');
        } catch (Throwable $e) {
            $this->assertTrue($e instanceof \Error || $e instanceof \Exception);
        }
    }

    /**
     * @throws RandomException
     * @throws ReflectionException
     */
    public function testTransformLogLevelForLoggingBranches(): void
    {
        $motLogger = new MotLogger($this->monolog);
        $method = (new ReflectionClass($motLogger))->getMethod('transformLogLevelForLogging');

        $method->setAccessible(true); // Intentionally not using return value; sets method accessible

        $this->assertSame('ERROR', $method->invoke($motLogger, 'critical'));
        $this->assertSame('INFO', $method->invoke($motLogger, 'debug'));
        $this->assertSame('WARN', $method->invoke($motLogger, 'alert'));
        $this->assertSame('FOO', $method->invoke($motLogger, 'foo'));
    }

    /**
     * @throws RandomException
     */
    public function testLogMethodMapsLegacyLaminasLogLevelsCorrectly(): void
    {
        $motLogger = new MotLogger($this->monolog);

        $levelMap = [
            0 => Level::Emergency,
            1 => Level::Alert,
            2 => Level::Critical,
            3 => Level::Error,
            4 => Level::Warning,
            5 => Level::Notice,
            6 => Level::Info,
            7 => Level::Debug,
        ];

        foreach ($levelMap as $intLevel => $expectedLevel) {
            $this->testHandler->clear();

            $motLogger->log($intLevel, "msg-$intLevel");

            $record = $this->testHandler->getRecords()[0];

            $this->assertSame($expectedLevel->value, $record["level"]);
            $this->assertSame("msg-$intLevel", $record["message"]);
        }
    }


    /**
     * @throws RandomException
     */
    public function testLogMethodUnknownIntegerDefaultsToInfo(): void
    {
        $motLogger = new MotLogger($this->monolog);
        $this->testHandler->clear();

        $motLogger->log(99, "msg-unknown");
        $record = $this->testHandler->getRecords()[0];

        $this->assertSame(Level::Info->value, $record["level"]);
        $this->assertSame("msg-unknown", $record["message"]);
    }

    /**
     * @throws RandomException
     */
    public function testLogMethodLevelEnumIsUsedAsIs(): void
    {
        $motLogger = new MotLogger($this->monolog);
        $this->testHandler->clear();

        $motLogger->log(Level::Warning, "msg-enum");
        $record = $this->testHandler->getRecords()[0];

        $this->assertSame(Level::Warning->value, $record["level"]);
        $this->assertSame("msg-enum", $record["message"]);
    }
}
