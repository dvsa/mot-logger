<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Factory;

use DvsaLogger\Factory\ApiRequestExtrasProcessorFactory;
use DvsaLogger\Processor\ApiRequestExtrasProcessor;
use DvsaLogger\Util\LogRecordTrait;
use Monolog\Level;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

class ApiRequestExtrasProcessorFactoryTest extends TestCase
{
    use LogRecordTrait;

    public function testCreateWithNewConfig(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->with('Config')->willReturn([
            'mot_logger' => [
                'request_uuid' => 'test-uuid-123',
            ],
        ]);

        $factory = new ApiRequestExtrasProcessorFactory();
        $processor = $factory($container, ApiRequestExtrasProcessor::class);

        $this->assertProcessorInjectsUuid($processor, 'test-uuid-123');
    }

    public function testCreateWithLegacyConfig(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->with('Config')->willReturn([
            'DvsaApplicationLogger' => [
                'RequestUUID' => 'legacy-uuid-456',
            ],
        ]);

        $factory = new ApiRequestExtrasProcessorFactory();
        $processor = $factory($container, ApiRequestExtrasProcessor::class);

        $this->assertProcessorInjectsUuid($processor, 'legacy-uuid-456');
    }

    public function testCreateWithNoUuid(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->with('Config')->willReturn([
            'mot_logger' => [],
        ]);

        $factory = new ApiRequestExtrasProcessorFactory();
        $processor = $factory($container, ApiRequestExtrasProcessor::class);

        $this->assertProcessorInjectsUuid($processor, '');
    }

    public function testCreateCatchesException(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->with('Config')
            ->willThrowException(new RuntimeException('Config not found'));

        $factory = new ApiRequestExtrasProcessorFactory();
        $processor = $factory($container, ApiRequestExtrasProcessor::class);

        $this->assertProcessorInjectsUuid($processor, '');
    }

    private function assertProcessorInjectsUuid(
        ApiRequestExtrasProcessor $processor,
        string $expectedUuid,
    ): void {
        $record = $this->createLogRecord(Level::Debug, 'test');

        $result = $processor($record);
        $this->assertArrayHasKey('api_request_uuid', $result->extra);
        $this->assertSame($expectedUuid, $result->extra['api_request_uuid']);
    }
}
