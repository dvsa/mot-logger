<?php

declare(strict_types=1);

namespace DvsaLoggerTests\Unit\Factory;

use DvsaLogger\Factory\MotLoggerAbstractFactory;
use DvsaLogger\Logger\MotLogger;
use DvsaLogger\Util\ContainerTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Random\RandomException;

class MotLoggerAbstractFactoryTest extends TestCase
{
    use ContainerTrait;

    /**
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     * @throws ContainerExceptionInterface
     */
    public function testInvokeCreatesNamedLogger(): void
    {
        $container = $this->createContainer([
            'Config' => [
                'mot_logger' => [
                    'loggers' => [
                        'custom_logger' => [
                            'channel' => 'custom-channel',
                        ],
                    ],
                ],
            ],
        ]);

        $factory = new MotLoggerAbstractFactory();

        self::assertTrue(
            $factory->canCreate($container, 'custom_logger')
        );

        $logger = $factory($container, 'custom_logger');

        self::assertInstanceOf(MotLogger::class, $logger);
        self::assertSame('custom-channel', $logger->getLogger()->getName());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testAbstractFactoryCanCreateNamedLogger(): void
    {
        $container = $this->createContainer([
            'Config' => [
                'mot_logger' => [
                    'loggers' => [
                        'custom_logger' => [],
                    ],
                ],
            ],
        ]);

        $factory = new MotLoggerAbstractFactory();

        self::assertTrue($factory->canCreate($container, 'custom_logger'));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testAbstractFactoryCannotCreateUnknownLogger(): void
    {
        $container = $this->createContainer([
            'Config' => [
                'mot_logger' => [
                    'loggers' => [
                        'custom_logger' => [],
                    ],
                ],
            ],
        ]);

        $factory = new MotLoggerAbstractFactory();

        self::assertFalse(
            $factory->canCreate($container, 'unknown_logger')
        );
    }

    public function testAbstractFactoryReturnsFalseWhenNoLoggersConfigured(): void
    {
        $container = $this->createContainer([
            'Config' => [
                'mot_logger' => [],
            ],
        ]);

        $factory = new MotLoggerAbstractFactory();

        self::assertFalse(
            $factory->canCreate($container, 'custom_logger')
        );
    }
}
