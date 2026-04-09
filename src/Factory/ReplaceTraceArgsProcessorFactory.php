<?php

declare(strict_types=1);

namespace DvsaLogger\Factory;

use DvsaLogger\Helper\BuildReplaceMapTrait;
use DvsaLogger\Helper\ConfigResolutionTrait;
use DvsaLogger\Processor\ReplaceTraceArgsProcessor;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ReplaceTraceArgsProcessorFactory implements FactoryInterface
{
    use BuildReplaceMapTrait;
    use ConfigResolutionTrait;

    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ReplaceTraceArgsProcessor {
        $motConfig = $this->resolveMotConfig($container);

        $replaceMap = $this->buildReplaceMap($motConfig);

        return new ReplaceTraceArgsProcessor($replaceMap);
    }
}
