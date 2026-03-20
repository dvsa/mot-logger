<?php

    declare(strict_types=1);

    namespace DvsaLogger\Util;

    use Monolog\Level;
    use Monolog\LogRecord;
    use Throwable;

trait LogRecordTrait
{
    /**
     * Creates default metadata for testing.
     *
     * @param Level $level
     * @param string $message
     * @param array<string, mixed>|null $extra
     * @param array|null $context
     * @return LogRecord
     */
    private function createLogRecord(
        Level $level,
        string $message,
        ?array $extra = null,
        ?array $context = [],
    ): LogRecord {
        $defaultExtra = [];

        if ($extra !== null) {
            $defaultExtra = array_merge($defaultExtra, $extra);
        }

        return new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: $level,
            message: $message,
            context: $context ?? [],
            extra: $defaultExtra,
        );
    }

    private function createLogRecordWithException(
        Throwable $exception,
        Level $level = Level::Error,
        string $message = 'error message',
        ?array $context = [],
    ): LogRecord {
        return new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: $level,
            message: $message,
            context: $context,
            extra: [
                '__dvsa_metadata__' => [
                    'microtimeTimestamp' => '2026-01-01T00:00:00.000000 Z',
                    'timestamp' => '2026-01-01T00:00:00.000P',
                    'logEntryType' => 'Exception',
                    'username' => 'testUser',
                    'token' => 'testToken',
                    'traceId' => 'trace-123',
                    'parentSpanId' => 'parent-span',
                    'spanId' => 'span-123',
                    'callerName' => 'TestClass::testMethod',
                    'level' => 'ERROR',
                    'exceptionType' => get_class($exception),
                    'errorCode' => $exception->getCode(),
                    'stacktrace' => $exception->getTrace(),
                ],
            ],
        );
    }
}
