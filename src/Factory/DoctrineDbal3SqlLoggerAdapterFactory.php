<?php

namespace DvsaLogger\Factory;

use DvsaLogger\Adapter\DoctrineDbal3SqlLoggerAdapter;
use DvsaLogger\Service\DoctrineQueryLoggerService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class DoctrineDbal3SqlLoggerAdapterFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null
    ): DoctrineDbal3SqlLoggerAdapter {
        return new DoctrineDbal3SqlLoggerAdapter(
            $container->get(DoctrineQueryLoggerService::class),
        );
    }
}
