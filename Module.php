<?php

declare(strict_types=1);

namespace DvsaLogger;

use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\ModuleRouteListener;
use Laminas\Mvc\MvcEvent;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class Module
{
    public function getConfig(): array
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * @param MvcEvent $event
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onBootstrap(MvcEvent $event): void
    {
        if (!class_exists(MvcEvent::class)) {
            return;
        }

        $application = $event->getApplication();
        $eventManager = $application->getEventManager();
        $serviceManager = $application->getServiceManager();

        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        if (!interface_exists(EventManagerInterface::class)) {
            return;
        }

        $sapiHelper = $serviceManager->get(Helper\SapiHelper::class);

        $requestListener = $serviceManager->get(Listener\RequestListener::class);
        $requestListener->attach($eventManager);

        $responseListener = $serviceManager->get(Listener\ResponseListener::class);
        $responseListener->attach($eventManager);

        if (!$sapiHelper->requestIsConsole) {
            $apiRequestListener = $serviceManager->get(Listener\ApiRequestListener::class);
            $apiRequestListener->attach($eventManager);

            $apiClientRequestListener = $serviceManager->get(Listener\ApiClientRequestListener::class);
            $apiClientRequestListener->attach($eventManager);

            $exceptionListener = $serviceManager->get(Listener\ExceptionListener::class);
            $exceptionListener->attach($eventManager);
        }
    }
}
