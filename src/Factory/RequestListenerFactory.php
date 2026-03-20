<?php

declare(strict_types=1);

namespace DvsaLogger\Factory;

use DvsaLogger\Listener\RequestListener;
use DvsaLogger\Logger\MotLogger;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class RequestListenerFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): RequestListener {
        $logger = $container->get(MotLogger::class);
        return new RequestListener($logger);
    }
}
