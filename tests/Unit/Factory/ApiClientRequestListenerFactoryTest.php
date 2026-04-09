<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Factory;

use DvsaLogger\Factory\ApiClientRequestListenerFactory;
use DvsaLogger\Listener\ApiClientRequestListener;
use DvsaLogger\Logger\MotLogger;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use ReflectionObject;

class ApiClientRequestListenerFactoryTest extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     */
    public function testCreate(): void
    {
        $logger = $this->createMock(MotLogger::class);
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(MotLogger::class)
            ->willReturn($logger);

        $factory = new ApiClientRequestListenerFactory();
        $listener = $factory($container, ApiClientRequestListener::class);

        $this->assertListenerHasLogger($listener, $logger);
    }

    private function assertListenerHasLogger(
        ApiClientRequestListener $listener,
        MotLogger $expectedLogger,
    ): void {
        $reflection = new ReflectionObject($listener);

        $property = $reflection->getProperty('logger');
        $property->setAccessible(true);

        $actualLogger = $property->getValue($listener);

        $this->assertSame(
            $expectedLogger,
            $actualLogger,
            'Listener should be constructed with the logger from the container',
        );
    }
}
