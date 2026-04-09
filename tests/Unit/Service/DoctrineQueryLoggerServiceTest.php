<?php

namespace DvsaLoggerTest\Unit\Service;

use DvsaLogger\Debugger\BacktraceDebugger;
use DvsaLogger\Debugger\Call;
use DvsaLogger\Logger\MotLogger;
use DvsaLogger\Service\DoctrineQueryLoggerService;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Random\RandomException;

class DoctrineQueryLoggerServiceTest extends TestCase
{
    /**
     * @throws RandomException
     */
    public function testItDoesNotLogIfNotEnabled(): void
    {
        [$service, $handler] = $this->createServiceAndHandler(false);

        $service->startQuery('SELECT * FROM users', [], []);
        $service->stopQuery();

        $this->assertEmpty($handler->getRecords());
    }

    /**
     * @throws RandomException
     */
    public function testItLogsQueryIfEnabled(): void
    {
        [$service, $handler] = $this->createServiceAndHandler(true);

        $service->startQuery('SELECT * FROM users', ['id' => 1], []);
        $service->stopQuery();

        $this->assertCount(1, $handler->getRecords());

        $record = $handler->getRecords()[0];
        $this->assertSame('', $record->message);
        $this->assertSame('SELECT * FROM users', $record->extra['query']);
        $this->assertArrayHasKey('parameters', $record->extra);
        $this->assertArrayHasKey('query_time', $record->extra);
    }

    /**
     * @throws RandomException
     */
    public function testItLogsRepositoryMethod(): void
    {
        $handler = new TestHandler();
        $monolog = new Logger('test', [$handler]);
        $motLogger = new MotLogger($monolog);

        $debugger = $this->createMock(BacktraceDebugger::class);
        $debugger->method('findCall')
            ->willReturn(new Call('MyRepository', 'findOne'));

        $service = new DoctrineQueryLoggerService($motLogger, true, $debugger);

        $service->startQuery('SELECT * FROM users', [], []);
        $service->stopQuery();

        $record = $handler->getRecords()[0];
        $context = json_decode($record->extra['context'], true);

        $this->assertSame('MyRepository', $context['repository_class']);
        $this->assertSame('findOne', $context['repository_method']);
    }

    /**
     * @throws RandomException
     */
    private function createServiceAndHandler(bool $enabled): array
    {
        $handler = new TestHandler();
        $monolog = new Logger('test', [$handler]);
        $motLogger = new MotLogger($monolog);
        $service = new DoctrineQueryLoggerService($motLogger, $enabled);
        return [$service, $handler];
    }
}
