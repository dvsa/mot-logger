<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Factory;

use DvsaLogger\Factory\ReplaceTraceArgsProcessorFactory;
use DvsaLogger\Processor\ReplaceTraceArgsProcessor;
use DvsaLogger\Util\LogRecordTrait;
use Monolog\Level;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

class ReplaceTraceArgsProcessorFactoryTest extends TestCase
{
    use LogRecordTrait;

    /**
     * @throws ContainerExceptionInterface
     */
    public function testCreateWithNewConfig(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->with('Config')
            ->willReturn([
                'mot_logger' => [
                    'mask_credentials' => [
                        'mask' => '***',
                        'fields' => ['password', 'secret'],
                    ],
                ],
            ]);

        $factory = new ReplaceTraceArgsProcessorFactory();
        $processor = $factory($container, ReplaceTraceArgsProcessor::class);

        $this->assertProcessorMasksFields(
            $processor,
            ['password' => 'myPass', 'secret' => 'mySecret', 'other' => 'ok'],
            ['password' => '***', 'secret' => '***', 'other' => 'ok'],
            'new config'
        );
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testCreateWithLegacyConfig(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->with('Config')
            ->willReturn([
                'DvsaLogger' => [
                    'maskDatabaseCredentials2' => [
                        'mask' => '***',
                        'argsToMask' => ['token']
                    ],
                ],
            ]);

        $factory = new ReplaceTraceArgsProcessorFactory();
        $processor = $factory($container, ReplaceTraceArgsProcessor::class);

        $this->assertProcessorMasksFields(
            $processor,
            ['token' => 'abc', 'foo' => 'bar'],
            ['token' => '***', 'foo' => 'bar'],
            'legacy config'
        );
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testCreateWithNoConfig(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->with('Config')
            ->willReturn([]);

        $factory = new ReplaceTraceArgsProcessorFactory();
        $processor = $factory($container, ReplaceTraceArgsProcessor::class);

        $this->assertProcessorMasksFields(
            $processor,
            ['foo' => 'bar'],
            ['foo' => 'bar'],
            'no config'
        );
    }

    private function assertProcessorMasksFields(
        ReplaceTraceArgsProcessor $processor,
        array $input,
        array $expected,
        string $label,
    ): void {
        $record = $this->createLogRecord(
            Level::Error,
            'msg',
            ['params' => $input]
        );
        $result = $processor($record);
        $actual = $result->extra['params'] ?? [];
        $this->assertSame($expected, $actual, "Masking failed for case: $label");
    }
}
