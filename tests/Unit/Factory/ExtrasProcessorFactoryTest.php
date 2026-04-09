<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Factory;

use DvsaLogger\Factory\ExtrasProcessorFactory;
use DvsaLogger\Processor\ExtrasProcessor;
use DvsaLogger\Util\LogRecordTrait;
use Monolog\Level;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

class ExtrasProcessorFactoryTest extends TestCase
{
    use LogRecordTrait;

    public function testCreateWithNewConfig(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->with('Config')
            ->willReturn([
                'mot_logger' => [
                    'request_uuid' => 'uuid-456',
                ],
            ]);

        $factory = new ExtrasProcessorFactory();
        $processor = $factory($container, ExtrasProcessor::class);

        $this->assertProcessorInjectsUuid($processor, 'uuid-456');
    }

    public function testCreateWithLegacyConfig(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->with('Config')
            ->willReturn([
                'DvsaApplicationLogger' => [
                    'RequestUUID' => 'legacy-uuid',
                ],
            ]);

        $factory = new ExtrasProcessorFactory();
        $processor = $factory($container, ExtrasProcessor::class);

        $this->assertProcessorInjectsUuid($processor, 'legacy-uuid');
    }

    public function testCreateWithNoUuid(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->with('Config')
            ->willReturn([
                'mot_logger' => [],
            ]);

        $factory = new ExtrasProcessorFactory();
        $processor = $factory($container, ExtrasProcessor::class);

        $this->assertProcessorInjectsUuid($processor, '');
    }

    public function testCreateCatchesException(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->with('Config')
            ->willThrowException(new RuntimeException('Config not available'));

        $factory = new ExtrasProcessorFactory();
        $processor = $factory($container, ExtrasProcessor::class);

        $this->assertInstanceOf(ExtrasProcessor::class, $processor);
        $record = $this->createLogRecord(Level::Info, 'test');
        $result = $processor($record);
        $this->assertArrayHasKey('request_uuid', $result->extra);
        $this->assertSame('', $result->extra['request_uuid']);
    }

    private function assertProcessorInjectsUuid(
        ExtrasProcessor $processor,
        string $expectedUuid,
    ): void {
        $record = $this->createLogRecord(Level::Info, 'test');
        $result = $processor($record);

        $this->assertArrayHasKey('request_uuid', $result->extra);
        $this->assertSame($expectedUuid, $result->extra['request_uuid']);
    }
}
