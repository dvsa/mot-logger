<?php

namespace DvsaLogger\Service;

use DvsaLogger\Debugger\BacktraceDebugger;
use DvsaLogger\Debugger\Call;
use Laminas\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class DoctrineQueryLoggerServiceTest extends TestCase
{
    /**
     * @var MockObject&LoggerInterface
     *
     * @psalm-suppress PropertyNotSetInConstructor constructor is defined in the base class
     */
    private $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testItDoesNotLogIfNotEnabled(): void
    {
        $queryLoggerService = new DoctrineQueryLoggerService($this->logger);

        $this->logger->expects($this->never())->method($this->anything());

        $queryLoggerService->startQuery('SELECT * FROM users', [], []);
        $queryLoggerService->stopQuery();
    }

    public function testItLogsQueryIfEnabled(): void
    {
        $queryLoggerService = new DoctrineQueryLoggerService($this->logger, true);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                '',
                $this->callback(function ($value) {
                        $this->assertArrayHasKey('query', $value);
                        $this->assertArrayHasKey('parameters', $value);
                        $this->assertArrayHasKey('types', $value);
                        $this->assertArrayHasKey('query_time', $value);

                        return true;
                })
            );

        $queryLoggerService->startQuery('SELECT * FROM users', [], []);
        $queryLoggerService->stopQuery();
    }

    public function testItLogsRepositoryMethod(): void
    {
        $debugger = $this->createMock(BacktraceDebugger::class);

        $queryLoggerService = new DoctrineQueryLoggerService($this->logger, true, $debugger);

        $debugger->expects($this->any())
            ->method('findCall')
            ->with('Repository')
            ->will($this->returnValue(new Call('MyRepository', 'findOne')));

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                '',
                $this->callback(function ($value) {
                    $this->assertArrayHasKey('context', $value);
                    $this->assertSame(
                        json_encode(['repository_class' => 'MyRepository', 'repository_method' => 'findOne']),
                        $value['context']
                    );

                    return true;
                })
            );

        $queryLoggerService->startQuery('SELECT * FROM users', [], []);
        $queryLoggerService->stopQuery();
    }
}
