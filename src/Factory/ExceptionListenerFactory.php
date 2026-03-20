<?php

declare(strict_types=1);

namespace DvsaLogger\Factory;

use DvsaLogger\Contract\TokenServiceInterface;
use DvsaLogger\Listener\ExceptionListener;
use DvsaLogger\Logger\MotLogger;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ExceptionListenerFactory implements FactoryInterface
{
    public function __invoke(
        Containerinterface $container,
        $requestedName,
        ?array $options = null,
    ): ExceptionListener {
        $logger = $container->get(MotLogger::class);

        $tokenService = null;

        try {
            $tokenService = $container->get(TokenServiceInterface::class);
        } catch (ServiceNotFoundException) {
        }

        return new ExceptionListener($logger, $tokenService);
    }
}
