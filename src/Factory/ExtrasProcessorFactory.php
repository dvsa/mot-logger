<?php

declare(strict_types=1);

namespace DvsaLogger\Factory;

use DvsaLogger\Helper\ConfigResolutionTrait;
use DvsaLogger\Processor\ExtrasProcessor;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * @deprecated This factory is retained for backward compatibility with legacy consuming apps.
 * @psalm-suppress DeprecatedClass
 * The new architecture embeds request extras directly into the logging context
 * at the call site (@see RequestListener) rather than using a separate processor.
 * New code should not use this factory - the ExtrasProcessor is now instantiated directly
 * where needed or injected via service configuration.
 */
class ExtrasProcessorFactory implements FactoryInterface
{
    use ConfigResolutionTrait;

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function __invoke(
        ContainerInterface $container,
        mixed $requestedName,
        ?array $options = null,
    ): ExtrasProcessor {
        $requestUuid = '';

        try {
            $motConfig = $this->resolveMotConfig($container);
            $requestUuid = $motConfig['request_uuid'] ?? $motConfig['RequestUUID'] ?? null;
        } catch (Throwable $exception) {
            error_log(sprintf(
                'Error resolving MOT config for ExtrasProcessorFactory: %s',
                $exception->getMessage()
            ));
        }

        /** @psalm-suppress DeprecatedClass */
        return new ExtrasProcessor([
            'request_uuid' => $requestUuid,
        ]);
    }
}
