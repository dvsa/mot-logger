<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Processor;

use DvsaLogger\Processor\ReplaceTraceArgsProcessor;
use DvsaLogger\Util\LogRecordTrait;
use Monolog\Level;
use PHPUnit\Framework\TestCase;

class ReplaceTraceArgsProcessorTest extends TestCase
{
    use LogRecordTrait;

    public function testItReplacesSensitiveValuesInTrace(): void
    {
        $processor = new ReplaceTraceArgsProcessor(['myPassword' => '********']);

        $record = $this->createLogRecord(
            Level::Error,
            'error',
            ['trace' => [['args' => ['myPassword' => 'secret123']]]]
        );

        $result = $processor($record);

        $this->assertStringNotContainsString(
            'myPassword',
            $result->extra['trace'][0]['args']['myPassword'],
        );
        $this->assertSame(
            '********',
            $result->extra['trace'][0]['args']['myPassword'],
        );
    }

    public function testItReplacesSensitiveValuesInParams(): void
    {
        $processor = new ReplaceTraceArgsProcessor(['password' => '********']);

        $record = $this->createLogRecord(
            Level::Error,
            'error',
            ['params' => ['password' => 'secret123']]
        );

        $result = $processor($record);

        $this->assertSame('********', $result->extra['params']['password']);
    }

    public function testSkipsDebugLevel(): void
    {
        $processor = new ReplaceTraceArgsProcessor(['secret' => '***']);

        $record = $this->createLogRecord(
            Level::Debug,
            'debug',
            ['trace' => [['args' => ['secret' => 'value123']]]]
        );

        $result = $processor($record);

        $this->assertSame(
            'value123',
            $result->extra['trace'][0]['args']['secret'],
        );
    }

    public function testItProcessCriticalLevel(): void
    {
        $processor = new ReplaceTraceArgsProcessor(['apiKey' => '********']);

        $record = $this->createLogRecord(
            Level::Critical,
            'crit',
            ['trace' => [['args' => ['apiKey' => 'xyz']]]]
        );

        $result = $processor($record);

        $this->assertSame(
            '********',
            $result->extra['trace'][0]['args']['apiKey'],
        );
    }

    public function testItReturnsUnchangedWhenNoTrace(): void
    {
        $processor = new ReplaceTraceArgsProcessor(['secret' => '***']);

        $record = $this->createLogRecord(
            Level::Error,
            'error',
            ['trace' => 'not an array']
        );

        $result = $processor($record);

        $this->assertSame('not an array', $result->extra['trace']);
    }

    public function testItHandlesEmptyReplaceMap(): void
    {
        $processor = new ReplaceTraceArgsProcessor([]);

        $record = $this->createLogRecord(
            Level::Error,
            'error',
            ['trace' => [['args' => ['value']]]]
        );

        $result = $processor($record);

        $this->assertSame($record->extra['trace'], $result->extra['trace']);
    }
}
