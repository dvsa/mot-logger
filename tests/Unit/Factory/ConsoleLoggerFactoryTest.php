<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Factory;

use DvsaLogger\Factory\ConsoleLoggerFactory;
use DvsaLogger\Logger\ConsoleLogger;
use DvsaLogger\Logger\MotLogger;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Random\RandomException;

class ConsoleLoggerFactoryTest extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testCreateWithMotLogger(): void
    {
        $monolog = $this->createMock(Logger::class);
        $motLogger = $this->createMock(MotLogger::class);
        $motLogger->method('getLogger')->willReturn($monolog);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(MotLogger::class)
            ->willReturn($motLogger);

        $factory = new ConsoleLoggerFactory();
        $logger = $factory($container, ConsoleLoggerFactory::class);

        $this->assertConsoleLoggerHasMonolog($logger, $monolog);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testCreateWithConsoleLogger(): void
    {
        $monolog = $this->createMock(Logger::class);
        $consoleLogger = new ConsoleLogger($monolog);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(MotLogger::class)
            ->willReturn($consoleLogger);

        $factory = new ConsoleLoggerFactory();
        $logger = $factory($container, ConsoleLoggerFactory::class);

        $this->assertSame(
            $consoleLogger,
            $logger,
            'Should return the same ConsoleLogger instance from the container',
        );
    }

    private function assertConsoleLoggerHasMonolog(
        ConsoleLogger $logger,
        Logger $expectedMonolog,
    ): void {
        $actualMonolog = $logger->getLogger();
        $this->assertSame(
            $expectedMonolog,
            $actualMonolog,
            'ConsoleLogger should be constructed with the correct Monolog instance',
        );
    }
}
