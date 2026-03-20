<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Factory;

use DvsaLogger\Contract\TokenServiceInterface;
use DvsaLogger\Factory\ExceptionListenerFactory;
use DvsaLogger\Listener\ExceptionListener;
use DvsaLogger\Logger\MotLogger;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use ReflectionObject;

class ExceptionListenerFactoryTest extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     */
    public function testCreateWithTokenService(): void
    {
        $logger = $this->createMock(MotLogger::class);
        $tokenService = $this->createMock(TokenServiceInterface::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [MotLogger::class, $logger],
                [TokenServiceInterface::class, $tokenService],
            ]);

        $factory = new ExceptionListenerFactory();
        $listener = $factory($container, ExceptionListener::class);

        $this->assertExceptionListener($listener, $logger, $tokenService);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testCreateWithoutTokenService(): void
    {
        $logger = $this->createMock(MotLogger::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function (string $name) use ($logger) {
                if ($name === MotLogger::class) {
                    return $logger;
                }
                throw new ServiceNotFoundException('TokenServiceInterface not found');
            });

        $factory = new ExceptionListenerFactory();
        $listener = $factory($container, ExceptionListener::class);

        $this->assertExceptionListener($listener, $logger, null);
    }

    /**
     * Helper to assert ExceptionListener internal state
     */
    private function assertExceptionListener($listener, $expectedLogger, $expectedTokenService): void
    {
        $this->assertInstanceOf(ExceptionListener::class, $listener);

        $ref = new ReflectionObject($listener);

        $loggerProp = $ref->getProperty('logger');
        $loggerProp->setAccessible(true);

        $tokenServiceProp = $ref->getProperty('tokenService');
        $tokenServiceProp->setAccessible(true);

        $this->assertSame(
            $expectedLogger,
            $loggerProp->getValue($listener),
            'Logger should match',
        );
        $this->assertSame(
            $expectedTokenService,
            $tokenServiceProp->getValue($listener),
            'TokenService should match',
        );
    }
}
