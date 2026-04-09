<?php

declare(strict_types=1);

namespace DvsaLogger\Factory;

use DvsaLogger\Helper\ConfigResolutionTrait;
use DvsaLogger\Logger\MotLogger;
use DvsaLogger\Service\DoctrineQueryLoggerService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class DoctrineQueryLoggerServiceFactory implements FactoryInterface
{
    use ConfigResolutionTrait;

    public function __invoke(
        ContainerInterface $container,
        mixed $requestedName,
        ?array $options = null,
    ): DoctrineQueryLoggerService {
        $logger = $container->get(MotLogger::class);

        $motConfig = $this->resolveMotConfig($container);

        $doctrineConfig = $motConfig['doctrine_query']
            ?? ($motConfig['loggers']['doctrine_query'] ?? []);

        $enabled = (bool) ($doctrineConfig['enabled'] ?? false);

        return new DoctrineQueryLoggerService($logger, $enabled);
    }
}
