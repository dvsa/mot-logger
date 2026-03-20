<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Processor;

use DvsaLogger\Processor\ApiRequestExtrasProcessor;
use DvsaLogger\Util\LogRecordTrait;
use Monolog\Level;
use PHPUnit\Framework\TestCase;

class ApiRequestExtrasProcessorTest extends TestCase
{
    use LogRecordTrait;

    public function testItAddsApiRequestExtras(): void
    {
        $processor = new ApiRequestExtrasProcessor([
           'uri' => 'https://api.example.com/v1/users',
            'request_method' => 'POST',
            'parameters' => '{"name": "Joe"}',
            'ip_address' => '10.0.0.1',
            'token' => 'Bearer xyz',
            'api_request_uuid' => 'req-uuid-123',
            'frontend_request_uuid' => 'fe-uuid-123',
            'user_agent' => 'Guzzle/7'
        ]);

        $record = $this->createLogRecord(Level::Debug, '');

        $result = $processor($record);

        $this->assertSame('https://api.example.com/v1/users', $result->extra['uri']);
        $this->assertSame('POST', $result->extra['request_method']);
        $this->assertSame('{"name": "Joe"}', $result->extra['parameters']);
        $this->assertSame('10.0.0.1', $result->extra['ip_address']);
        $this->assertSame('Bearer xyz', $result->extra['token']);
        $this->assertSame('req-uuid-123', $result->extra['api_request_uuid']);
        $this->assertSame('fe-uuid-123', $result->extra['frontend_request_uuid']);
        $this->assertSame('Guzzle/7', $result->extra['user_agent']);
    }

    public function testItDefaultsToEmptyStrings(): void
    {
        $processor = new ApiRequestExtrasProcessor([]);

        $record = $this->createLogRecord(Level::Debug, '');

        $result = $processor($record);

        $this->assertSame('', $result->extra['uri']);
        $this->assertSame('', $result->extra['parameters']);
        $this->assertSame('', $result->extra['request_method']);
        $this->assertSame('', $result->extra['ip_address']);
        $this->assertSame('', $result->extra['php_session_id']);
        $this->assertSame('', $result->extra['route']);
        $this->assertSame('', $result->extra['api_request_uuid']);
        $this->assertSame('', $result->extra['frontend_request_uuid']);
        $this->assertSame('', $result->extra['token']);
        $this->assertSame('', $result->extra['user_agent']);
    }
}
