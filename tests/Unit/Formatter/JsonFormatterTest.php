<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Formatter;

use DvsaLogger\Formatter\JsonFormatter;
use DvsaLogger\Util\LogRecordTrait;
use Monolog\Level;
use PHPUnit\Framework\TestCase;

class JsonFormatterTest extends TestCase
{
    use LogRecordTrait;

    private const FIELD_ORDER = [
        'microtimeTimestamp',
        'timestamp',
        'priority',
        'priorityName',
        'level',
        'logEntryType',
        'username',
        'token',
        'traceId',
        'parentSpanId',
        'spanId',
        'callerName',
        'logger_name',
        'exceptionType',
        'message',
        'extra',
        'stacktrace',
    ];
    private JsonFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new JsonFormatter();
    }

    public function testJsonFormatContainsAllFieldsInCorrectOrder(): void
    {
        $record = $this->createLogRecord(
            Level::Error,
            'test error message',
            [
                '__dvsa_metadata__' => [
                    'microtimeTimestamp' => '2026-01-01 00:00:00.000000 Z',
                    'timestamp' => '2026-01-01 00:00:00.000P',
                    'level' => 'ERROR',
                    'logEntryType' => 'Exception',
                    'username' => 'test-user',
                    'token' => 'test-token',
                    'traceId' => 'test-trace-id',
                    'parentSpanId' => 'test-parent-span-id',
                    'spanId' => 'test-span-id',
                    'callerName' => 'TestClass::testMethod',
                    'logger_name' => 'test-logger',
                    'exceptionType' => 'RuntimeException',
                    'errorCode' => 500,
                    'stacktrace' => '#0 /path/to/file.php(10)',
                ],
                'custom_extra' => 'extra value',
            ],
        );

        $result = $this->formatter->format($record);
        $decoded = json_decode($result, true);

        foreach (self::FIELD_ORDER as $field) {
            $this->assertArrayHasKey($field, $decoded, "Field '$field' should be present");
        }
    }

    public function testPriorityAndPriorityNameMapping(): void
    {
        foreach (Level::cases() as $level) {
            $record = $this->createLogRecord($level, 'test message');

            $result = $this->formatter->format($record);
            $decoded = json_decode($result, true);

            $this->assertSame(
                $level->value, $decoded['priority'],
                "Priority for {$level->name} should be {$level->value}",
            );
            $this->assertSame(
                strtoupper($level->name),
                $decoded['priorityName'],
                "PriorityName for {$level->name} should be " . strtoupper($level->name),
            );
        }
    }

    public function testExtraFieldsIncluded(): void
    {
        $record = $this->createLogRecord(
            Level::Info,
            'test message',
            [
                'custom_field1' => 'value1',
                'custom_field2' => ['nested' => 'value2'],
            ],
        );

        $result = $this->formatter->format($record);
        $decoded = json_decode($result, true);

        $this->assertArrayHasKey('extra', $decoded);
        $this->assertSame('value1', $decoded['extra']['custom_field1']);
        $this->assertSame(['nested' => 'value2'], $decoded['extra']['custom_field2']);
    }

    public function testExtraExcludesMetadataKey(): void
    {
        $record = $this->createLogRecord(
            Level::Info,
            'test message',
            ['user_extra' => 'should be in extra'],
        );

        $result = $this->formatter->format($record);
        $decoded = json_decode($result, true);

        $this->assertArrayHasKey('extra', $decoded);
        $this->assertArrayNotHasKey('__dvsa_metadata__', $decoded['extra']);
        $this->assertSame('should be in extra', $decoded['extra']['user_extra']);
    }

    public function testMissingMetadataFieldsNotIncluded(): void
    {
        $record = $this->createLogRecord(
            Level::Info,
            'test message',
            [
                '__dvsa_metadata__' => [
                    'microtimeTimestamp' => '2026-01-01 00:00:00.000000 Z',
                    'username' => 'new user',
                ],
            ],
        );

        $result = $this->formatter->format($record);
        $decoded = json_decode($result, true);

        $this->assertSame('2026-01-01 00:00:00.000000 Z', $decoded['microtimeTimestamp']);
        $this->assertSame('new user', $decoded['username']);
        $this->assertArrayNotHasKey('traceId', $decoded);
        $this->assertArrayNotHasKey('token', $decoded);
    }

    public function testJsonFormatBatchWithEmptyArray(): void
    {
        $result = $this->formatter->formatBatch([]);
        $this->assertSame('', $result);
    }

    public function testFormatBatchConcatenatesJsonStrings(): void
    {
        $records = [
            $this->createLogRecord(Level::Info, 'first'),
            $this->createLogRecord(Level::Alert, 'second'),
        ];

        $result = $this->formatter->formatBatch($records);

        $this->assertStringContainsString('first', $result);
        $this->assertStringContainsString('second', $result);
    }

    public function testFormatBatchHandlersMultipleRecords(): void
    {
        $records = [
            $this->createLogRecord(Level::Debug, 'debug message'),
            $this->createLogRecord(Level::Info, 'info message'),
            $this->createLogRecord(Level::Error, 'error message'),
            $this->createLogRecord(Level::Warning, 'warning message'),
            $this->createLogRecord(Level::Notice, 'notice message'),
            $this->createLogRecord(Level::Critical, 'warning message'),
        ];

        $result = $this->formatter->formatBatch($records);

        foreach (['debug', 'info', 'warning', 'error', 'critical'] as $msg) {
            $this->assertStringContainsString(strtoupper($msg), $result);
        }
    }
}
