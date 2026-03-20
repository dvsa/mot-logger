<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Handler;

use DateTimeImmutable;
use DvsaLogger\Handler\ErrorLogFallbackHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ErrorLogFallbackHandlerTest extends TestCase
{
    private string $errorLogFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->errorLogFile = sys_get_temp_dir() . '/php_error_log_' . uniqid();
        ini_set('error_log', $this->errorLogFile);

        // Clear the file before each test
        file_put_contents($this->errorLogFile, '');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (file_exists($this->errorLogFile)) {
            unlink($this->errorLogFile);
        }
    }

    private function getErrorLogContents(): string
    {
        return file_exists($this->errorLogFile) ? file_get_contents($this->errorLogFile) : '';
    }

    public function testHandlesLogRecord(): void
    {
        $handler = new ErrorLogFallbackHandler();
        $logger = new Logger('test', [$handler]);
        $logger->info('fallback test');

        $log = $this->getErrorLogContents();

        $this->assertStringContainsString('fallback test', $log);
    }

    public function testHandlesLogRecordWithException(): void
    {
        $handler = new ErrorLogFallbackHandler();
        $logger = new Logger('test', [$handler]);

        $exception = new RuntimeException('test exception');
        $logger->error('error', ['exception' => $exception]);

        $log = $this->getErrorLogContents();

        // The handler logs the message and stacktrace, not the exception message
        $this->assertStringContainsString('error', $log);
        $this->assertStringContainsString('Stacktrace', $log);
    }

    public function testHandlesLogRecordWithStacktraceInExtra(): void
    {
        $handler = new ErrorLogFallbackHandler();

        $logger = new Logger('test', [$handler]);
        $logger->pushProcessor(function (LogRecord $record): LogRecord {
            return $record->with(extra: array_merge($record->extra, ['stacktrace' => 'trace123']));
        });

        $logger->info('msg');
        $log = $this->getErrorLogContents();

        $this->assertStringContainsString('trace123', $log);
    }

    public function testStacktraceInExtraTakesPrecedenceOverException(): void
    {
        $handler = new ErrorLogFallbackHandler();
        $exception = new RuntimeException('ex');

        $logger = new Logger('test', [$handler]);
        $logger->pushProcessor(function (LogRecord $record): LogRecord {
            return $record->with(extra: array_merge(
                $record->extra,
                ['stacktrace' => 'trace456']
            ),);
        });

        $logger->info('msg', ['exception' => $exception]);

        $log = $this->getErrorLogContents();

        $this->assertStringContainsString('trace456', $log);
        $this->assertStringNotContainsString('ex', $log); // stacktrace should take precedence
    }

    public function testHandlesLogRecordWithNoStacktraceOrException(): void
    {
        $handler = new ErrorLogFallbackHandler();

        $logger = new Logger('test', [$handler]);
        $logger->info('plain');

        $log = $this->getErrorLogContents();

        $this->assertStringContainsString('plain', $log);
    }

    public function testHandlesDifferentLogLevels(): void
    {
        $handler = new ErrorLogFallbackHandler();

        $logger = new Logger('test', [$handler]);
        $logger->warning('warn');

        $log = $this->getErrorLogContents();
        $this->assertStringContainsString('warn', $log);

        $logger->debug('debug');

        $log = $this->getErrorLogContents();
        $this->assertStringContainsString('debug', $log);
    }

    public function testHandlesNonStringStacktrace(): void
    {
        $handler = new ErrorLogFallbackHandler();

        $logger = new Logger('test', [$handler]);
        $logger->pushProcessor(function (LogRecord $record): LogRecord {
            return $record->with(extra: array_merge(
                $record->extra,
                ['stacktrace' => ['not', 'a', 'string']]
            ),);
        });

        $logger->info('msg');
        $log = $this->getErrorLogContents();

        $this->assertStringContainsString('msg', $log);
        // PHP will log 'Array' for array-to-string conversion
        $this->assertStringContainsString('Array', $log);
    }

    public function testHandlesEmptyMessage(): void
    {
        $handler = new ErrorLogFallbackHandler();

        $logger = new Logger('test', [$handler]);
        $logger->info('');

        $log = $this->getErrorLogContents();

        $this->assertNotEmpty($log); // Should still log timestamp
    }

    public function testCoverageSanity(): void
    {
        $handler = new ErrorLogFallbackHandler();
        $record = new LogRecord(
            datetime: new DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'test',
            context: [],
            extra: [],
        );

        $handler->handle($record);
        $log = $this->getErrorLogContents();

        $this->assertStringContainsString('test', $log);
    }
}
