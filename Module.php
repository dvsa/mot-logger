<?php

namespace DvsaLogger;

use Laminas\Mvc\ModuleRouteListener;
use Laminas\Mvc\MvcEvent;

/**
 * Class Module
 *
 * @package DvsaLogger
 */
class Module
{
    /**
     * @param MvcEvent $e
     */
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        $config = $e->getApplication()->getServiceManager()->get('config');
        $serviceManager = $e->getApplication()->getServiceManager();
        foreach ($config['DvsaLogger']['listeners'] as $listenerConfig) {
            if ($listenerConfig['enabled'] === true) {
                $logger = $serviceManager->get($listenerConfig['loggerFactory']);
                $class = $listenerConfig['listenerClass'];
                $listener = new $class();
                $listener->setLogger($logger);
                $eventManager->attach($listener);
            }
        }
        return;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return [
            \Laminas\Loader\ClassMapAutoloader::class => [
                __DIR__ . '/autoload_classmap.php',
            ],
            \Laminas\Loader\StandardAutoloader::class => [
                'namespaces' => [
                    'DvsaLogger' => __DIR__ . '/src/DvsaLogger',
                ],
            ],
        ];
    }
}
