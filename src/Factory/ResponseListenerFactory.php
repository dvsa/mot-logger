<?php

declare(strict_types=1);

namespace DvsaLogger\Factory;

use DvsaLogger\Listener\ResponseListener;
use DvsaLogger\Logger\MotLogger;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ResponseListenerFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ResponseListener {
        $logger = $container->get(MotLogger::class);
        return new ResponseListener($logger);
    }
}
