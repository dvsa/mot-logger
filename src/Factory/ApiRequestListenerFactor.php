<?php

    declare(strict_types=1);

    namespace DvsaLogger\Factory;

    use DvsaLogger\Listener\ApiRequestListener;
    use DvsaLogger\Logger\MotLogger;
    use Laminas\ServiceManager\Factory\FactoryInterface;
    use Psr\Container\ContainerInterface;

class ApiRequestListenerFactor implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): ApiRequestListener
    {
        $logger = $container->get(MotLogger::class);
        return new ApiRequestListener($logger);
    }
}
