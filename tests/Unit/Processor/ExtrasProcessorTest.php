<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Processor;

use DvsaLogger\Processor\ExtrasProcessor;
use DvsaLogger\Util\LogRecordTrait;
use Monolog\Level;
use PHPUnit\Framework\TestCase;

class ExtrasProcessorTest extends TestCase
{
    use LogRecordTrait;

    public function testItAddsExtrasTopRecord(): void
    {
        $extras = [
          'uri' => 'https://example.com/api/v1/test',
            'ip_address' => '127.0.0.1',
            'php_session_id' => 'abc123',
            'route' => '/api/test',
            'request_uuid' => 'uuid-123',
            'token' => 'Bearer 123',
            'user_agent' => 'PHPUnit',
            'memory_usage' => 0
        ];

        $processor = new ExtrasProcessor($extras);

        $record = $this->createLogRecord(Level::Debug, '');

        $result = $processor($record);

        $this->assertSame('https://example.com/api/v1/test', $result->extra['uri']);
        $this->assertSame('127.0.0.1', $result->extra['ip_address']);
        $this->assertSame('abc123', $result->extra['php_session_id']);
        $this->assertSame('/api/test', $result->extra['route']);
        $this->assertSame('uuid-123', $result->extra['request_uuid']);
        $this->assertSame('Bearer 123', $result->extra['token']);
        $this->assertSame('PHPUnit', $result->extra['user_agent']);
        $this->assertSame(0, $result->extra['memory_usage']);
    }

    public function testItTruncatesUri(): void
    {
        $longUri = str_repeat('a', 300);
        $extras = ['uri' => $longUri];

        $processor = new ExtrasProcessor($extras);

        $record = $this->createLogRecord(Level::Debug, '');

        $result = $processor($record);

        $this->assertLessThanOrEqual(255, strlen($result->extra['uri']));
    }
}
