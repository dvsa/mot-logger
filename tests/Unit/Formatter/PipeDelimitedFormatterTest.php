<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Formatter;

use DvsaLogger\Formatter\PipeDelimitedFormatter;
use DvsaLogger\Util\LogRecordTrait;
use Exception;
use Monolog\Level;
use PHPUnit\Framework\TestCase;

class PipeDelimitedFormatterTest extends TestCase
{
    use LogRecordTrait;

    private PipeDelimitedFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new PipeDelimitedFormatter(false);
    }

    public function testPipeDelimitedFormat(): void
    {
        $record = $this->createLogRecord(Level::Info, 'test message');

        $result = $this->formatter->format($record);

        $this->assertStringStartsWith('^^*', $result);
        $this->assertStringContainsString('||', $result);
        $this->assertStringContainsString('test message', $result);
    }

    public function testPipeDelimitedFormatWithExceptionFields(): void
    {
        $formatter = new PipeDelimitedFormatter(true);
        $record = $this->createLogRecordWithException(
            new Exception('test exception', 500),
            Level::Error,
            'exception message'
        );

        $result = $formatter->format($record);

        $this->assertStringStartsWith('^^*', $result);
        $this->assertStringContainsString('||', $result);
        $this->assertStringContainsString('exception message', $result);
        $this->assertStringContainsString(Exception::class, $result);
        $this->assertStringContainsString('500', $result);
    }

    public function testFormatBatch(): void
    {
        $records = [
            $this->createLogRecord(Level::Info, 'first'),
            $this->createLogRecord(Level::Error, 'second'),
        ];

        $result = $this->formatter->formatBatch($records);

        $this->assertStringStartsWith('^^', $result);
        $this->assertStringContainsString('first', $result);
        $this->assertStringContainsString('second', $result);
    }
}
