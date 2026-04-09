<?php

declare(strict_types=1);

namespace DvsaLogger\Factory;

use DvsaLogger\Helper\BuildReplaceMapTrait;
use DvsaLogger\Helper\ConfigResolutionTrait;
use DvsaLogger\Logger\SystemLogger;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class SystemLoggerFactory implements FactoryInterface
{
    use BuildReplaceMapTrait;
    use ConfigResolutionTrait;

    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): SystemLogger {
        $motConfig = $this->resolveMotConfig($container);

        $replaceMap = $this->buildReplaceMap($motConfig);

        return new SystemLogger($replaceMap);
    }
}
