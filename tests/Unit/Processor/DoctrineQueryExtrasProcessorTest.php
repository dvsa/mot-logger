<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Processor;

use DvsaLogger\Processor\DoctrineQueryExtrasProcessor;
use DvsaLogger\Util\LogRecordTrait;
use Monolog\Level;
use PHPUnit\Framework\TestCase;

class DoctrineQueryExtrasProcessorTest extends TestCase
{
    use LogRecordTrait;

    public function testItAddsDoctrineQueryExtras(): void
    {
        $processor = new DoctrineQueryExtrasProcessor([
            'api_endpoint_uri' => 'https://api.example.com/test',
            'api_query_string' => '?page=1',
            'api_post_data' => '{"foo":"bar"}',
            'api_method' => 'GET',
            'ip' => '10.0.0.1',
            'session_id' => 'session_id',
            'token' => 'Bearer xyz',
            'api_request_uuid' => 'req-uuid-123',
            'remote_request_uuid' => 'remote-uuid-123',
        ]);

        $record = $this->createLogRecord(Level::Debug, '');

        $result = $processor($record);

        $this->assertSame('https://api.example.com/test', $result->extra['api_endpoint_uri']);
        $this->assertSame('GET', $result->extra['api_method']);
        $this->assertSame('req-uuid-123', $result->extra['api_request_uuid']);
        $this->assertSame('remote-uuid-123', $result->extra['remote_request_uuid']);
    }

    public function testItDefaultsToEmptyString(): void
    {
        $processor = new DoctrineQueryExtrasProcessor([]);

        $record = $this->createLogRecord(Level::Debug, '');

        $result = $processor($record);

        $this->assertSame('', $result->extra['api_endpoint_uri']);
        $this->assertSame('', $result->extra['api_query_string']);
        $this->assertSame('', $result->extra['api_post_data']);
        $this->assertSame('', $result->extra['api_method']);
        $this->assertSame('', $result->extra['ip']);
        $this->assertSame('', $result->extra['session_id']);
        $this->assertSame('', $result->extra['cookie']);
        $this->assertSame('', $result->extra['token']);
        $this->assertSame('', $result->extra['remote_request_uri']);
        $this->assertSame('', $result->extra['api_request_uuid']);
    }
}
