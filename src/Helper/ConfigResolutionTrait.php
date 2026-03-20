<?php

declare(strict_types=1);

namespace DvsaLogger\Helper;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

trait ConfigResolutionTrait
{
    /**
     * @param Containerinterface $container
     * @return array<string, mixed>
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function resolveMotConfig(Containerinterface $container): array
    {
        $config = $container->get('Config');
        return $config['mot_logger']
            ?? $config['DvsaApplicationLogger']
            ?? $config['DvsaLogger']
            ?? [];
    }
}
