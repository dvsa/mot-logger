<?php

namespace DvsaLogger\Factory;

use DvsaLogger\Processor\ApiRequestExtras;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Class ExtrasProcessorFactory
 *
 * @package DvsaDoctrineLogger\Factory
 */
class ApiRequestExtrasFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return ApiRequestExtras|object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        // get config
        $config = $container->get('config');
        //get request
        $request = $container->get('Request');
        // inject request into the extras processor
        $processor = new ApiRequestExtras($request, $config['DvsaLogger']['RequestUUID']);
        return $processor;
    }
}
