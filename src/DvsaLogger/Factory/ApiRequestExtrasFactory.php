<?php

namespace DvsaLogger\Factory;

use DvsaLogger\Processor\ApiRequestExtras;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Http\Request;

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
        /** @var array */
        $config = $container->get('config');
        //get request
        /** @var Request */
        $request = $container->get('Request');
        // inject request into the extras processor
        /** @var array */
        $dvsaLogger = $config['DvsaLogger'];
        /** @var string */
        $uuid = $dvsaLogger['RequestUUID'];
        $processor = new ApiRequestExtras($request, $uuid);

        return $processor;
    }
}
