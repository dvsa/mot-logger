<?php

declare(strict_types=1);

namespace DvsaLogger\Factory;

use DvsaLogger\Listener\ApiClientRequestListener;
use DvsaLogger\Logger\MotLogger;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ApiClientRequestListenerFactory implements FactoryInterface
{
    public function __invoke(
        Containerinterface $container,
        $requestedName,
        ?array $options = null,
    ): ApiClientRequestListener {
        $logger = $container->get(MotLogger::class);
        return new ApiClientRequestListener($logger);
    }
}
