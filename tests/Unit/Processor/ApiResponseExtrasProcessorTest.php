<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Processor;

use DvsaLogger\Processor\ApiResponseExtrasProcessor;
use DvsaLogger\Util\LogRecordTrait;
use Monolog\Level;
use PHPUnit\Framework\TestCase;

class ApiResponseExtrasProcessorTest extends TestCase
{
    use LogRecordTrait;

    public function testItAddsApiResponseExtras(): void
    {
        $processor = new ApiResponseExtrasProcessor([
            'status_code' => 200,
            'content_type' => 'application/json',
            'response_content' => '{"id":1}',
            'api_request_uuid' => 'req-uuid-xyz',
            'frontend_request_uuid' => 'req-uuid-xyz',
            'token' => 'Bearer 123',
        ]);

        $record = $this->createLogRecord(Level::Debug, '');

        $result = $processor($record);

        $this->assertSame(200, $result->extra['status_code']);
        $this->assertSame('application/json', $result->extra['content_type']);
        $this->assertSame('{"id":1}', $result->extra['response_content']);
        $this->assertSame('req-uuid-xyz', $result->extra['api_request_uuid']);
        $this->assertSame('req-uuid-xyz', $result->extra['frontend_request_uuid']);
        $this->assertSame('Bearer 123', $result->extra['token']);
    }

    public function testItDefaultsToEmptyValues(): void
    {
        $processor = new ApiResponseExtrasProcessor([]);

        $record = $this->createLogRecord(Level::Debug, '');

        $result = $processor($record);

        $this->assertSame('', $result->extra['status_code']);
        $this->assertSame('', $result->extra['content_type']);
        $this->assertSame('', $result->extra['response_content']);
        $this->assertSame('', $result->extra['api_request_uuid']);
        $this->assertSame('', $result->extra['frontend_request_uuid']);
        $this->assertSame('', $result->extra['token']);
    }
}
