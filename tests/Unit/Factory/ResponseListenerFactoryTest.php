<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Factory;

use DvsaLogger\Factory\ResponseListenerFactory;
use DvsaLogger\Listener\ResponseListener;
use DvsaLogger\Logger\MotLogger;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use ReflectionClass;

class ResponseListenerFactoryTest extends TestCase
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

        $factory = new ResponseListenerFactory();
        $listener = $factory($container, ResponseListener::class);

        $this->assertListenerHasLogger($listener, $logger);
    }

    private function assertListenerHasLogger(
        ResponseListener $listener,
        MotLogger $expectedLogger,
    ): void {
        $reflection = new ReflectionClass($listener);
        $property = $reflection->getProperty('logger');
        $property->setAccessible(true);

        $actualLogger = $property->getValue($listener);

        $this->assertEquals($expectedLogger, $actualLogger);
    }
}
