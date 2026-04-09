<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Factory;

use DvsaLogger\Factory\ApiResponseExtrasProcessorFactory;
use DvsaLogger\Processor\ApiResponseExtrasProcessor;
use DvsaLogger\Util\LogRecordTrait;
use Monolog\Level;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

class ApiResponseExtrasProcessorFactoryTest extends TestCase
{
    use LogRecordTrait;

    public function testCreateWithNewConfig(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->with('Config')->willReturn([
            'mot_logger' => [
                'request_uuid' => 'test-uuid',
            ],
        ]);

        $factory = new ApiResponseExtrasProcessorFactory();
        $processor = $factory($container, ApiResponseExtrasProcessor::class);

        $this->assertProcessorInjectsUuid($processor, 'test-uuid');
    }

    public function testCreateWithLegacyConfig(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->with('Config')->willReturn([
            'DvsaApplicationLogger' => [
                'RequestUUID' => 'legacy-uuid',
            ],
        ]);

        $factory = new ApiResponseExtrasProcessorFactory();
        $processor = $factory($container, ApiResponseExtrasProcessor::class);

        $this->assertProcessorInjectsUuid($processor, 'legacy-uuid');
    }

    public function testCreateWithNoUuid(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->with('Config')->willReturn([
            'mot_logger' => [],
        ]);

        $factory = new ApiResponseExtrasProcessorFactory();
        $processor = $factory($container, ApiResponseExtrasProcessor::class);

        $this->assertProcessorInjectsUuid($processor, '');
    }

    public function testCreateCatchesException(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->with('Config')
            ->willThrowException(new RuntimeException('Config not found'));

        $factory = new ApiResponseExtrasProcessorFactory();
        $processor = $factory($container, ApiResponseExtrasProcessor::class);

        $this->assertProcessorInjectsUuid($processor, '');
    }

    private function assertProcessorInjectsUuid(
        ApiResponseExtrasProcessor $processor,
        string $expectedUuid,
    ): void {
        $record = $this->createLogRecord(
            Level::Info,
            'msg',
            [],
        );
        $result = $processor($record);
        $this->assertArrayHasKey('api_request_uuid', $result->extra);
        $this->assertSame($expectedUuid, $result->extra['api_request_uuid']);
    }
}
