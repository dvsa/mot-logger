<?php

declare(strict_types=1);

namespace DvsaLogger\Factory;

use DvsaLogger\Logger\MotLogger;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Random\RandomException;
use Psr\Container\ContainerInterface;

final class MotLoggerAbstractFactory implements AbstractFactoryInterface
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function canCreate(
        ContainerInterface $container,
        $requestedName
    ): bool {
        error_log('###### AbstractFactory called for: ' . $requestedName);
        $config = $container->get('Config');
        return isset(($config['mot_logger']['loggers'] ?? [])[$requestedName]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): MotLogger
    {
        return (new MotLoggerFactory())(
            $container,
            $requestedName,
            $options
        );
    }
}
