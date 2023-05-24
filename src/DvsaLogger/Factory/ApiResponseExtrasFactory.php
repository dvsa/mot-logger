<?php

namespace DvsaLogger\Factory;

use DvsaLogger\Processor\ApiResponseExtras;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Class ExtrasProcessorFactory
 *
 * @package DvsaDoctrineLogger\Factory
 */
class ApiResponseExtrasFactory implements FactoryInterface
{

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return ApiResponseExtras
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        // get config
        $config = $container->get('config');
        //get request
        $request = $container->get('Request');
        $response = $container->get('Response');
        // inject request into the extras processor
        $processor = new ApiResponseExtras($request, $response, $config['DvsaLogger']['RequestUUID']);
        return $processor;
    }
}
