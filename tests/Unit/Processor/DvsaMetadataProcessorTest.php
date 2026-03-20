<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Processor;

use DvsaLogger\Processor\DvsaMetadataProcessor;
use DvsaLogger\Util\LogRecordTrait;
use Monolog\Level;
use PHPUnit\Framework\TestCase;

class DvsaMetadataProcessorTest extends TestCase
{
    use LogRecordTrait;

    public function testItMovesMetadataToExtra(): void
    {
        $processor = new DvsaMetadataProcessor();

        $record = $this->createLogRecord(
            Level::Debug,
            'test',
            [
                '__dvsa_metadata__' => [
                    'username' => 'test-user',
                    'token' => 'abc123',
                    'level' => 'DEBUG'
                ]
            ],
        );

        $result = $processor($record);

        $this->assertArrayHasKey('__dvsa_metadata__', $result->extra);
        $this->assertSame('test-user', $result->extra['__dvsa_metadata__']['username']);
        $this->assertSame('DEBUG', $result->extra['__dvsa_metadata__']['level']);
        $this->assertSame('abc123', $result->extra['__dvsa_metadata__']['token']);
    }

    public function testItPreservesExistingExtra(): void
    {
        $processor = new DvsaMetadataProcessor();

        $record = $this->createLogRecord(
            Level::Debug,
            'test',
            [
                'pre_existing_extra' => 'preserved',
                '__dvsa_metadata__' => [
                    'level' => 'DEBUG'
                ],
            ],
        );

        $result = $processor($record);

        $this->assertArrayHasKey('pre_existing_extra', $result->extra);
        $this->assertArrayHasKey('__dvsa_metadata__', $result->extra);
        $this->assertSame('preserved', $result->extra['pre_existing_extra']);
    }

    public function testItMovesContextToExtra(): void
    {
        $processor = new DvsaMetadataProcessor();

        $record = $this->createLogRecord(
            Level::Debug,
            'test',
            ['trace' => ['line']],
            ['other' => 'value'],
        );

        $result = $processor($record);

        $this->assertArrayNotHasKey('__dvsa_metadata__', $result->extra);
        $this->assertArrayHasKey('trace', $result->extra);
        $this->assertArrayHasKey('other', $result->extra);
        $this->assertSame('value', $result->extra['other']);
    }
}
