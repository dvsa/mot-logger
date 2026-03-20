<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Factory;

use DvsaLogger\Factory\DoctrineQueryExtrasProcessorFactory;
use DvsaLogger\Processor\DoctrineQueryExtrasProcessor;
use DvsaLogger\Util\LogRecordTrait;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

class DoctrineQueryExtrasProcessorFactoryTest extends TestCase
{
    use LogRecordTrait;

    /**
     * @throws ContainerExceptionInterface
     */

    public function testCreateWithNewConfig(): void
    {
        $container = $this->createStub(Containerinterface::class);
        $container->method('get')->with('Config')->willReturn([
            'mot_logger' => [
                'request_uuid' => 'test-uuid-123',
            ],
        ]);

        $factory = new DoctrineQueryExtrasProcessorFactory();
        $processor = $factory($container, DoctrineQueryExtrasProcessor::class);

        $this->assertProcessorInjectsUuid($processor, 'test-uuid-123');
    }


    /**
     * @throws ContainerExceptionInterface
     */
    public function testCreateWithLegacyConfig(): void
    {
        $container = $this->createStub(Containerinterface::class);
        $container->method('get')->with('Config')->willReturn([
        'DvsaApplicationLogger' => [
            'RequestUUID' => 'legacy-uuid-456',
        ],
        ]);

        $factory = new DoctrineQueryExtrasProcessorFactory();
        $processor = $factory($container, DoctrineQueryExtrasProcessor::class);

        $this->assertProcessorInjectsUuid($processor, 'legacy-uuid-456');
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testCreateWithNoUuid(): void
    {
        $container = $this->createStub(Containerinterface::class);
        $container->method('get')->with('Config')->willReturn([
        'mot_logger' => [],
        ]);

        $factory = new DoctrineQueryExtrasProcessorFactory();
        $processor = $factory($container, DoctrineQueryExtrasProcessor::class);

        $this->assertProcessorInjectsUuid($processor, '');
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testCreateCatchesException(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->with('Config')
            ->willThrowException(new RuntimeException('Config not available'));

        $factory = new DoctrineQueryExtrasProcessorFactory();
        $processor = $factory($container, DoctrineQueryExtrasProcessor::class);

        $record = $this->createLogRecord(Level::Info, 'test');
        $result = $processor($record);

        $this->assertArrayHasKey('api_request_uuid', $result->extra);
        $this->assertSame('', $result->extra['api_request_uuid']);
    }

    private function assertProcessorInjectsUuid(
        DoctrineQueryExtrasProcessor $processor,
        string $expectedUuid,
    ): void {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable('2024-01-01T00:00:00Z'),
            channel: 'test',
            level: Level::Info,
            message: 'msg',
            context: [],
            extra: []
        );
        $result = $processor($record);
        $this->assertArrayHasKey('api_request_uuid', $result->extra);
        $this->assertSame($expectedUuid, $result->extra['api_request_uuid']);
    }
}
