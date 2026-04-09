<?php

declare(strict_types=1);

namespace DvsaLogger\Factory;

use DvsaLogger\Logger\ConsoleLogger;
use DvsaLogger\Logger\MotLogger;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Random\RandomException;

class ConsoleLoggerFactory implements FactoryInterface
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ConsoleLogger {
        $logger = $container->get(MotLogger::class);
        if ($logger instanceof ConsoleLogger) {
            return $logger;
        }

        return new ConsoleLogger($logger->getLogger());
    }
}
