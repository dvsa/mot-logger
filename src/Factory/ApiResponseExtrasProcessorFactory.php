<?php

declare(strict_types=1);

namespace DvsaLogger\Factory;

use DvsaLogger\Helper\ConfigResolutionTrait;
use DvsaLogger\Processor\ApiResponseExtrasProcessor;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * @deprecated This class is deprecated and will be removed in a future release.
 * @psalm-suppress DeprecatedClass
 * This factory is retained for backward compatibility with legacy consuming apps.
 * The new architecture embeds API response extras directly into the logging context
 * at the call site (@see ResponseListener), rather than using a separate processor.
 * New code should not use this factory. The ApiResponseExtrasProcessor is now
 * instantiated directly where needed or injected via service configuration.
 */
class ApiResponseExtrasProcessorFactory implements FactoryInterface
{
    use ConfigResolutionTrait;

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function __invoke(
        ContainerInterface $container,
        mixed $requestedName,
        ?array $options = null,
    ): ApiResponseExtrasProcessor {
        $requestUuid = '';

        try {
            $motConfig = $this->resolveMotConfig($container);
            $requestUuid = $motConfig['request_uuid'] ?? $motConfig['RequestUUID'] ?? '';
        } catch (Throwable $exception) {
            error_log(sprintf(
                "Error resolving MOT config for ApiResponseExtrasProcessorFactory: %s",
                $exception->getMessage(),
            ));
        }

        /** @psalm-suppress DeprecatedClass */
        return new ApiResponseExtrasProcessor([
            'api_request_uuid' => $requestUuid,
        ]);
    }
}
