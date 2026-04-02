<?php

declare(strict_types=1);

namespace DvsaLogger\Factory;

use DvsaLogger\Helper\ConfigResolutionTrait;
use DvsaLogger\Processor\ApiRequestExtrasProcessor;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * @deprecated This class is deprecated and will be removed in a future release.
 * @psalm-suppress DeprecatedClass
 * This factory is retained for backward compatibility with legacy consuming apps.
 * The new architecture embeds API request extras directly into the logging context
 * at the call site (@see ApiRequestListener), rather than using a separate processor.
 * New code should not use this factory. The ApiRequestExtrasProcessor is now
 * instantiated directly where needed or injected via service configuration.
 */
class ApiRequestExtrasProcessorFactory implements FactoryInterface
{
    use ConfigResolutionTrait;

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function __invoke(
        Containerinterface $container,
        $requestedName,
        ?array $options = null,
    ): ApiRequestExtrasProcessor {
        $requestUuid = '';

        try {
            $motConfig = $this->resolveMotConfig($container);
            $requestUuid = $motConfig['request_uuid'] ?? $motConfig['RequestUUID'] ?? '';
        } catch (Throwable $exception) {
                error_log(sprintf(
                    'Error resolving MOT config for ApiRequestExtrasProcessor: %s',
                    $exception->getMessage()
                ));
        }

        /** @psalm-suppress DeprecatedClass */
        return new ApiRequestExtrasProcessor([
            'api_request_uuid' => $requestUuid,
        ]);
    }
}
