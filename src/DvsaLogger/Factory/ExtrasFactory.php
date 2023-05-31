<?php

namespace DvsaLogger\Factory;

use DvsaLogger\Processor\Extras;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Class ExtrasProcessorFactory
 *
 * @package DvsaDoctrineLogger\Factory
 */
class ExtrasFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return Extras|object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        // get config
        $config = $container->get('config');

        $routeMatch = $container->get('Application')->getMvcEvent()->getRouteMatch();
        $tokenService = $container->get('tokenService');

        $identity = $container->get('MotIdentityProvider');
        // get request
        $request = $container->get('Request');
        // inject request into the extras processor
        $processor = new Extras($request, $identity, $tokenService, $routeMatch, $config['DvsaLogger']['RequestUUID']);
        return $processor;
    }
}
