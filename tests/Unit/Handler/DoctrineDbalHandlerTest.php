<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Handler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use DvsaLogger\Handler\DoctrineDbalHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

class DoctrineDbalHandlerTest extends TestCase
{
    public function testInsertsRecord(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('insert')
            ->with(
                'my_table',
                $this->callback(function (array $data) {
                    return isset($data['timestamp'])
                        && isset($data['priority'])
                        && isset($data['priorityName'])
                        && isset($data['message'])
                        && $data['message'] === 'test message'
                        && $data['priorityName'] === 'INFO';
                }),
            );

        $handler = new DoctrineDbalHandler(
            $connection,
            'my_table',
            null,
            Level::Debug,
        );

        $logger = new Logger('test', [$handler]);
        $logger->info('test message');
    }

    public function testAppliesColumnMap(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('insert')
            ->with(
                'my_table',
                $this->callback(function (array $data) {
                    return isset($data['log_message'])
                        && $data['log_message'] === 'mapped message'
                        && !isset($data['message']);
                }),
            );

        $columnMap = [
            'log_message' => 'message',
            'log_priority' => 'priority',
        ];

        $handler = new DoctrineDbalHandler(
            $connection,
            'my_table',
            $columnMap,
            Level::Debug,
        );

        $logger = new Logger('test', [$handler]);
        $logger->info('mapped message');
    }

    public function testRespectsLevel(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())
            ->method('insert');

        $handler = new DoctrineDbalHandler(
            $connection,
            'my_table',
            null,
            Level::Error,
        );

        $logger = new Logger('test', [$handler]);
        $logger->info('this should not be logged');
    }

    public function testTimestampFormat(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('insert')
            ->with(
                'my_table',
                $this->callback(function (array $data) {
                    // Accept timestamps with or without microseconds
                    return preg_match(
                        '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}(\.\d+)?$/',
                        $data['timestamp']
                    ) === 1;
                }),
            );

        $handler = new DoctrineDbalHandler(
            $connection,
            'my_table',
            null,
            Level::Debug,
        );

        $logger = new Logger('test', [$handler]);
        $logger->info('timestamp test');
    }

    public function testUsesDvsaTransformedLevel(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('insert')
            ->with(
                'my_table',
                $this->callback(function (array $data) {
                    return $data['priorityName'] === 'ERROR'
                        && $data['priority'] === Level::Critical->value;
                }),
            );

        $handler = new DoctrineDbalHandler(
            $connection,
            'my_table',
            null,
            Level::Debug,
        );

        $logger = new Logger('test', [$handler]);
        $logger->pushProcessor(function (LogRecord $record): LogRecord {
            return $record->with(extra: array_merge($record->extra, [
                    '__dvsa_metadata__' => ['level' => 'ERROR'],
                ]));
        });
        $logger->critical('critical message');
    }

    public function testFallsBackToMonologLevel(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('insert')
            ->with(
                'my_table',
                $this->callback(function (array $data) {
                    return $data['priorityName'] === 'INFO'
                        && $data['priority'] === Level::Info->value;
                }),
            );

        $handler = new DoctrineDbalHandler(
            $connection,
            'my_table',
            null,
            Level::Debug,
        );

        $logger = new Logger('test', [$handler]);
        $logger->info('info message');
    }

    public function testColumnMapWithArrayMapping(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('insert')
            ->with(
                'my_table',
                $this->callback(function (array $data) {
                    // Should set 'array_col' to null because the mapping logic is odd
                    return array_key_exists('array_col', $data) && $data['array_col'] === null;
                }),
            );

        $columnMap = [
            'array_col' => [], // Should result in null
        ];

        $handler = new DoctrineDbalHandler(
            $connection,
            'my_table',
            $columnMap,
            Level::Debug,
        );

        $logger = new Logger('test', [$handler]);
        $logger->info('test');
    }

    public function testColumnMapWithEmptyArrayValue(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('insert')
            ->with(
                'my_table',
                $this->callback(function (array $data) {
                    // Should set 'empty_col' to null
                    return array_key_exists('empty_col', $data) && $data['empty_col'] === null;
                }),
            );

        $columnMap = [
            'empty_col' => [],
        ];

        $handler = new DoctrineDbalHandler(
            $connection,
            'my_table',
            $columnMap,
            Level::Debug,
        );

        $logger = new Logger('test', [$handler]);
        $logger->info('test');
    }

    public function testExtraFieldsAreJsonEncoded(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('insert')
            ->with(
                'my_table',
                $this->callback(function (array $data) {
                    return isset($data['foo']) && $data['foo'] === json_encode(['bar' => 'baz']);
                }),
            );

        $handler = new DoctrineDbalHandler(
            $connection,
            'my_table',
            null,
            Level::Debug,
        );

        $logger = new Logger('test', [$handler]);
        $logger->pushProcessor(function (LogRecord $record): LogRecord {
            return $record->with(extra: array_merge($record->extra, [
                'foo' => ['bar' => 'baz'],
            ]));
        });
        $logger->info('test');
    }

    public function testExceptionIsPropagated(): void
    {
        $this->expectException(\RuntimeException::class);
        $connection = $this->createMock(Connection::class);
        $connection->method('insert')
            ->willThrowException(new \RuntimeException('Database operation failed'));

        $handler = new DoctrineDbalHandler(
            $connection,
            'my_table',
            null,
            Level::Debug,
        );

        $logger = new Logger('test', [$handler]);
        $logger->info('should throw');
    }

    public function testAllFieldsPresentInOutput(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('insert')
            ->with(
                'my_table',
                $this->callback(function (array $data) {
                    return isset(
                        $data['timestamp'],
                        $data['priority'],
                        $data['priorityName'],
                        $data['message'],
                    );
                }),
            );
        $handler = new DoctrineDbalHandler(
            $connection,
            'my_table',
            null,
            Level::Debug,
        );

        $logger = new Logger('test', [$handler]);
        $logger->info('all fields');
    }

    public function testNonArrayExtraValues(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('insert')
            ->with(
                'my_table',
                $this->callback(function (array $data) {
                    return isset($data['foo']) && $data['foo'] === 'bar';
                }),
            );
        $handler = new DoctrineDbalHandler(
            $connection,
            'my_table',
            null,
            Level::Debug,
        );

        $logger = new Logger('test', [$handler]);
        $logger->pushProcessor(function (LogRecord $record): LogRecord {
            return $record->with(extra: array_merge($record->extra, [
                'foo' => 'bar',
            ]));
        });
        $logger->info('test');
    }
}
