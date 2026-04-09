<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Processor;

use DvsaLogger\Processor\SensitiveDataProcessor;
use DvsaLogger\Util\LogRecordTrait;
use Monolog\Level;
use PHPUnit\Framework\TestCase;

class SensitiveDataProcessorTest extends TestCase
{
    use LogRecordTrait;

    public function testItMasksSensitiveValuesInExtraTrace(): void
    {
        $processor = new SensitiveDataProcessor(['secretPassword' => '*********']);

        $record = $this->createLogRecord(
            Level::Error,
            'An error occurred',
            ['trace' => ['line1 with secretPassword', 'line2 with secretPassword']],
        );

        $result = $processor($record);

        $this->assertStringNotContainsString('secretPassword', $result->extra['trace'][0]);
    }

    public function testItDoesNotProcessAboveErrorLevel(): void
    {
        $processor = new SensitiveDataProcessor(['secret' => '***']);

        $record = $this->createLogRecord(
            Level::Debug,
            'debug',
            ['trace' => ['line with secret']],
        );

        $result = $processor($record);

        $this->assertStringContainsString('secret', $result->extra['trace'][0]);
    }

    public function testItProcessesErrorLevel(): void
    {
        $processor = new SensitiveDataProcessor(['my-secret' => '***']);

        $record = $this->createLogRecord(
            Level::Error,
            'err',
            ['trace' => ['line with my-secret']],
        );

        $result = $processor($record);

        $this->assertStringContainsString('***', $result->extra['trace'][0]);
        $this->assertStringNotContainsString('my-secret', $result->extra['trace'][0]);
    }

    public function testItHandlesEmptyReplaceMap(): void
    {
        $processor = new SensitiveDataProcessor([]);

        $record = $this->createLogRecord(
            Level::Error,
            'err',
            ['trace' => ['sensitive data']],
        );

        $result = $processor($record);

        $this->assertStringContainsString('sensitive data', $result->extra['trace'][0]);
    }

    public function testItMasksMultipleValues(): void
    {
        $processor = new SensitiveDataProcessor([
            'password' => '***',
            'apiKey' => '"###',
        ]);

        $record = $this->createLogRecord(
            Level::Error,
            'err',
            ['trace' => ['line with password and apiKey']],
        );

        $result = $processor($record);

        $this->assertStringContainsString('***', $result->extra['trace'][0]);
        $this->assertStringContainsString('###', $result->extra['trace'][0]);
        $this->assertStringNotContainsString('password', $result->extra['trace'][0]);
        $this->assertStringNotContainsString('apiKey', $result->extra['trace'][0]);
    }

    public function testItMasksSensitiveValuesInContextTrace(): void
    {
        $processor = new SensitiveDataProcessor(['secret' => 'MASKED']);

        $record = $this->createLogRecord(
            Level::Error,
            'An error occurred',
            [],
            ['trace' => ['line with secret']]
        );

        $result = $processor($record);

        $this->assertStringContainsString('MASKED', $result->context['trace'][0]);
        $this->assertStringNotContainsString('secret', $result->context['trace'][0]);
    }

    public function testItHandlesNestedArraysInContextTrace(): void
    {
        $processor = new SensitiveDataProcessor(['secret' => '********']);
        $record = $this->createLogRecord(
            Level::Error,
            'err',
            [],
            [
                'trace' => [
                    'level1' => [
                        'level2' => [
                            'contains secret'
                        ],
                    ],
                ],
            ],
        );

        $result = $processor($record);

        $this->assertStringContainsString(
            '********',
            $result->context['trace']['level1']['level2'][0],
        );
        $this->assertStringNotContainsString(
            'secret',
            $result->context['trace']['level1']['level2'][0],
        );
    }

    public function testItHandlesNonStringValuesInContextTrace(): void
    {
        $processor = new SensitiveDataProcessor(['foo' => 'bar']);
        $record = $this->createLogRecord(
            Level::Error,
            'err',
            null,
            ['trace' => [123, true, null, ['deep' => 456]]]
        );

        $result = $processor($record);

        $this->assertSame(123, $result->context['trace'][0]);
        $this->assertTrue($result->context['trace'][1]);
        $this->assertNull($result->context['trace'][2]);
        $this->assertSame(456, $result->context['trace'][3]['deep']);
    }

    public function testItHandlesEmptyContextTrace(): void
    {
        $processor = new SensitiveDataProcessor(['foo' => 'bar']);
        $record = $this->createLogRecord(
            Level::Error,
            'err',
            null,
            ['trace' => []]
        );

        $result = $processor($record);

        $this->assertSame([], $result->context['trace']);
    }

    public function testItHandlesNonArrayContextTrace(): void
    {
        $processor = new SensitiveDataProcessor(['foo' => 'bar']);
        $record = $this->createLogRecord(
            Level::Error,
            'err',
            null,
            ['trace' => 'foo string']
        );

        $result = $processor($record);

        $this->assertSame('foo string', $result->context['trace']);
    }

    public function testItHandlesMissingContextTrace(): void
    {
        $processor = new SensitiveDataProcessor(['foo' => 'bar']);
        $record = $this->createLogRecord(
            Level::Error,
            'err'
        );

        $result = $processor($record);

        $this->assertArrayNotHasKey('trace', $result->context);
    }
}
