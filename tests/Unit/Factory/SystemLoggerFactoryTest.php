<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Factory;

use DvsaLogger\Factory\SystemLoggerFactory;
use DvsaLogger\Logger\SystemLogger;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use ReflectionObject;

class SystemLoggerFactoryTest extends TestCase
{
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
                            'fields' => ['password']
                        ],
                    ],
                ]);

            $factory = new SystemLoggerFactory();
            $logger = $factory($container, SystemLogger::class);

            $this->assertInstanceOf(SystemLogger::class, $logger);
            $this->assertLoggerMaskConfig($logger, '***', ['password']);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testCreateWithLegacyConfig(): void
    {
            $container = $this->createStub(ContainerInterface::class);
            $container->method('get')->with('Config')->willReturn([
                'DvsaApplicationLogger' => [
                    'maskDatabaseCredentials2' => [
                        'mask' => '###',
                        'argsToMask' => ['secret']
                    ],
                ],
            ]);

            $factory = new SystemLoggerFactory();
            $logger = $factory($container, SystemLogger::class);

            $this->assertInstanceOf(SystemLogger::class, $logger);
            $this->assertLoggerMaskConfig($logger, '###', ['secret']);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testCreateWithNoConfig(): void
    {
            $container = $this->createStub(ContainerInterface::class);
            $container->method('get')->with('Config')->willReturn([]);

            $factory = new SystemLoggerFactory();
            $logger = $factory($container, SystemLogger::class);

            $this->assertInstanceOf(SystemLogger::class, $logger);
            $this->assertLoggerMaskConfig($logger, '***', []);
    }

    /**
     * Helper to assert the logger's mask and fields config via reflection
     */
    private function assertLoggerMaskConfig(
        SystemLogger $logger,
        string $expectedMask,
        array $expectedFields,
    ): void {
        $reflection = new ReflectionObject($logger);

        if ($reflection->hasProperty('processor')) {
            $processorProp = $reflection->getProperty('processor');
            $processorProp->setAccessible(true);
            $processor = $processorProp->getValue($logger);

            if ($processor !== null) {
                $procReflection = new ReflectionObject($processor);

                if ($procReflection->hasProperty('replaceMap')) {
                    $replaceMapProp = $procReflection->getProperty('replaceMap');
                    $replaceMapProp->setAccessible(true);

                    $replaceMap = $replaceMapProp->getValue($processor);

                    $this->assertSame(
                        $expectedMask,
                        $replaceMap['mask'] ?? null,
                        'Mask value should match config',
                    );
                    $this->assertSame(
                        $expectedFields,
                        $replaceMap['fields'] ?? $replaceMap['argsToMask'] ?? [],
                        'Fields/argsToMask should match config',
                    );
                }
            }
        }
    }
}
