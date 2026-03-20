<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Processor;

use DvsaLogger\Processor\TokenExclusionProcessor;
use DvsaLogger\Util\LogRecordTrait;
use Monolog\Level;
use PHPUnit\Framework\TestCase;

class TokenExclusionProcessorTest extends TestCase
{
    use LogRecordTrait;

    public function testItRemovesTokenFromMetadata(): void
    {
        $processor = new TokenExclusionProcessor();

        $record = $this->createLogRecord(
            Level::Error,
            'error message',
            [],
            [
                '__dvsa_metadata__' => [
                    'username'  => 'test-user',
                    'token'     => 'secret-token-123',
                    'traceId'   => 'trace-333',
                ],
            ],
        );

        $result = $processor($record);

        $this->assertArrayHasKey('username', $result->context['__dvsa_metadata__']);
        $this->assertArrayHasKey('traceId', $result->context['__dvsa_metadata__']);
        $this->assertArrayNotHasKey('token', $result->context['__dvsa_metadata__']);
    }

    public function testItHandlesNoMetadata(): void
    {
        $processor = new TokenExclusionProcessor();

        $record = $this->createLogRecord(
            Level::Error,
            'error message',
            [],
            ['other' => 'data'],
        );

        $result = $processor($record);
        $this->assertSame($record, $result);
    }

    public function testItHandlesEmptyMetadata(): void
    {
        $processor = new TokenExclusionProcessor();

        $record = $this->createLogRecord(
            Level::Error,
            'error',
            [],
            ['__dvsa_metadata__' => [],],
        );

        $result = $processor($record);
        $this->assertSame([], $result->context['__dvsa_metadata__']);
    }

    public function testItHandlesMetadataWithoutToken(): void
    {
        $processor = new TokenExclusionProcessor();

        $record = $this->createLogRecord(
            Level::Error,
            'error',
            [],
            [
                '__dvsa_metadata__' => [
                    'username' => 'test-user',
                    'traceId'   => 'trace-111',
                ],
            ],
        );

        $result = $processor($record);

        $this->assertArrayHasKey('username', $result->context['__dvsa_metadata__']);
        $this->assertArrayHasKey('traceId', $result->context['__dvsa_metadata__']);
    }
}
