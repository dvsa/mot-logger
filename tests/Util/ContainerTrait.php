<?php

declare(strict_types=1);

namespace DvsaLogger\Util;

use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Psr\Container\ContainerInterface;

trait ContainerTrait
{
    private function createContainer(array $services): ContainerInterface
    {
        return new class ($services) implements ContainerInterface {
            public function __construct(private readonly array $services)
            {
            }

            public function get(string $id): mixed
            {
                if (!$this->has($id)) {
                    throw new ServiceNotFoundException(sprintf('Service "%s" not found in container.', $id));
                }
                return $this->services[$id];
            }

            public function has(string $id): bool
            {
                return array_key_exists($id, $this->services);
            }
        };
    }
}
