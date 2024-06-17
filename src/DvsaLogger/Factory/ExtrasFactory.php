<?php

namespace DvsaLogger\Factory;

use DvsaLogger\Processor\Extras;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Core\Service\MotFrontendIdentityProvider;
use DvsaAuthentication\Service\WebAccessTokenService;

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
        /** @var array */
        $config = $container->get('config');

        $routeMatch = $container->get('Application')->getMvcEvent()->getRouteMatch();

        /** @var WebAccessTokenService */
        $tokenService = $container->get('tokenService');

        /** @var MotFrontendIdentityProvider */
        $identity = $container->get('MotIdentityProvider');
        // get request
        /** @var \Laminas\Http\Request */
        $request = $container->get('Request');
        // inject request into the extras processor
        /** @var array */
        $dvsaLogger = $config['DvsaLogger'];
        $processor = new Extras($request, $identity, $tokenService, $routeMatch, $dvsaLogger['RequestUUID']);
        return $processor;
    }
}
