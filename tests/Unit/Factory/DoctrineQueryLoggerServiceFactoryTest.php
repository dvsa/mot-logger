<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Factory;

use DvsaLogger\Factory\DoctrineQueryLoggerServiceFactory;
use DvsaLogger\Logger\MotLogger;
use DvsaLogger\Service\DoctrineQueryLoggerService;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use ReflectionObject;
use RuntimeException;

class DoctrineQueryLoggerServiceFactoryTest extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     */
    public function testCreateDisabledByDefault(): void
    {
        $logger = $this->createMock(MotLogger::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function (string $name) use ($logger) {
                if ($name === MotLogger::class) {
                    return $logger;
                }
                return ['mot_logger' => []];
            });

        $factory = new DoctrineQueryLoggerServiceFactory();
        $service = $factory($container, DoctrineQueryLoggerService::class);

        $this->assertDoctrineQueryLoggerService($service, $logger, false);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testCreateWithEnabled(): void
    {
        $logger = $this->createMock(MotLogger::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function (string $name) use ($logger) {
                if ($name === MotLogger::class) {
                    return $logger;
                }
                return ['mot_logger' => ['doctrine_query' => ['enabled' => true]]];
            });

        $factory = new DoctrineQueryLoggerServiceFactory();
        $service = $factory($container, DoctrineQueryLoggerService::class);

        $this->assertDoctrineQueryLoggerService($service, $logger, true);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testCreateWithLegacyConfig(): void
    {
        $logger = $this->createMock(MotLogger::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function (string $name) use ($logger) {
                if ($name === MotLogger::class) {
                    return $logger;
                }
                return ['DvsaLogger' => ['loggers' => ['doctrine_query' => ['enabled' => true]]]];
            });

        $factory = new DoctrineQueryLoggerServiceFactory();
        $service = $factory($container, DoctrineQueryLoggerService::class);

        $this->assertDoctrineQueryLoggerService($service, $logger, true);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testCreateCatchesException(): void
    {
        $logger = $this->createMock(MotLogger::class);
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive([MotLogger::class], ['Config'])
            ->willReturnOnConsecutiveCalls(
                $logger,
                $this->throwException(new RuntimeException('Config not available'))
            );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Config not available');
        $factory = new DoctrineQueryLoggerServiceFactory();
        $factory($container, DoctrineQueryLoggerService::class);
    }

    private function assertDoctrineQueryLoggerService(
        $service,
        $expectedLogger,
        bool $expectedEnabled,
    ): void {
        $this->assertInstanceOf(DoctrineQueryLoggerService::class, $service);
        $ref = new ReflectionObject($service);

        $loggerProp = $ref->getProperty('logger');
        $loggerProp->setAccessible(true);

        $enabledProp = $ref->getProperty('enabled');
        $enabledProp->setAccessible(true);

        $this->assertSame(
            $expectedLogger,
            $loggerProp->getValue($service),
            'Logger should match',
        );
        $this->assertSame(
            $expectedEnabled,
            $enabledProp->getValue($service),
            'Enabled flag should match',
        );
    }
}
