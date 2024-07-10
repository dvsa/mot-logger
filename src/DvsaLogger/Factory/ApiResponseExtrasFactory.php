<?php

namespace DvsaLogger\Factory;

use DvsaLogger\Processor\ApiResponseExtras;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Http\Request;
use Laminas\Http\Response;

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
        /** @var array */
        $config = $container->get('config');
        //get request
        /** @var Request */
        $request = $container->get('Request');
        /** @var Response */
        $response = $container->get('Response');
        // inject request into the extras processor
        /** @var array */
        $dvsaLogger = $config['DvsaLogger'];
        /** @var string */
        $uuid = $dvsaLogger['RequestUUID'];
        $processor = new ApiResponseExtras($request, $response, $uuid);

        return $processor;
    }
}
