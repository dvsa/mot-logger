<?php

namespace DvsaLogger\Factory;

use DvsaLogger\Processor\DoctrineQueryExtras;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Class DoctrineQueryExtrasProcessorFactory
 *
 * @package DvsaLogger\Factory
 */
class DoctrineQueryExtrasFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return DoctrineQueryExtras
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        // get config
        $config = $container->get('config');
        // get request
        $request = $container->get('Request');
        // inject request into the extras processor
        $processor = new DoctrineQueryExtras($request, $config['DvsaLogger']['RequestUUID']);
        return $processor;
    }
}
