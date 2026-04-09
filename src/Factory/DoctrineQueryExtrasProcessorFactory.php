<?php

declare(strict_types=1);

namespace DvsaLogger\Factory;

use DvsaLogger\Helper\ConfigResolutionTrait;
use DvsaLogger\Processor\DoctrineQueryExtrasProcessor;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * @deprecated This factory is retained for backward compatibility with legacy consuming apps.
 * The new architecture embeds Doctrine query extras directly into the logging context
 * at the call site (@see DoctrineQueryLoggerService), rather than using a separate processor.
 * New code should not use this factory. The DoctrineQueryExtrasProcessor is
 * now instantiated directly where needed or injected via service configuration
 */
class DoctrineQueryExtrasProcessorFactory implements FactoryInterface
{
    use ConfigResolutionTrait;

    public function __invoke(
        ContainerInterface $container,
        mixed $requestedName,
        ?array $options = null,
    ): DoctrineQueryExtrasProcessor {
        $requestUuid = '';

        try {
            $motConfig = $this->resolveMotConfig($container);
            $requestUuid = $motConfig['request_uuid'] ?? $motConfig['RequestUUID'] ?? '';
        } catch (Throwable $exception) {
            error_log(sprintf(
                'Error resolving MOT config for DoctrineQueryExtrasProcessor: %s',
                $exception->getMessage()
            ));
        }

        return new DoctrineQueryExtrasProcessor([
            'api_request_uuid' => $requestUuid,
        ]);
    }
}
