<?php

namespace DvsaLogger\Factory;

use DvsaLogger\Service\DoctrineQueryLoggerService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Class DoctrineQueryLoggerServiceFactory
 *
 * @package DvsaLogger\Factory
 */
class DoctrineQueryLoggerServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return DoctrineQueryLoggerService|object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $enabled = false;
        /** @var array */
        $config = $container->get('config');
        if (
            isset($config['DvsaLogger'])
            && is_array($config['DvsaLogger'])
            && isset($config['DvsaLogger']['loggers'])
            && is_array($config['DvsaLogger']['loggers'])
            && isset($config['DvsaLogger']['loggers']['doctrine_query'])
            && is_array($config['DvsaLogger']['loggers']['doctrine_query'])
            && isset($config['DvsaLogger']['loggers']['doctrine_query']['enabled'])
        ) {
            /** @var bool */
            $enabled = $config['DvsaLogger']['loggers']['doctrine_query']['enabled'];
        }

        return new DoctrineQueryLoggerService(
            $container->get('DvsaLogger\DoctrineQueryLogger'),
            $enabled
        );
    }
}
