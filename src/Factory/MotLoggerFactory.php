<?php

declare(strict_types=1);

namespace DvsaLogger\Factory;

use Doctrine\DBAL\Connection;
use DvsaLogger\Contract\IdentityProviderInterface;
use DvsaLogger\Contract\TokenServiceInterface;
use DvsaLogger\Formatter\JsonFormatter;
use DvsaLogger\Formatter\PipeDelimitedFormatter;
use DvsaLogger\Handler\DoctrineDbalHandler;
use DvsaLogger\Helper\BuildReplaceMapTrait;
use DvsaLogger\Helper\DatabaseConnectionResolver;
use DvsaLogger\Helper\UuidGeneratorTrait;
use DvsaLogger\Logger\MotLogger;
use DvsaLogger\Processor\ReplaceTraceArgsProcessor;
use DvsaLogger\Processor\SensitiveDataProcessor;
use DvsaLogger\Processor\TokenExclusionProcessor;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Monolog\ErrorHandler;
use Monolog\Handler\NoopHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\ProcessorInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Random\RandomException;

/**
 * Factory that creates a fully configured MotLogger from an array configuration.
 * Implements Laminas\ServiceManager\Factory\FactoryInterface for Laminas DI integration.
 *
 * Supports legacy config keys for backward compatibility:
 *  - Config root: mot_logger (new) | DvsaApplicationLogger (legacy) | DvsaLogger (legacy)
 *  - Error handler: register_error_handler (new) | registerExceptionHandler (legacy)
 *  - Credential masking: mask_credentials.fields (new) | maskDatabaseCredentials2.argsToMask (legacy)
 */
readonly class MotLoggerFactory implements FactoryInterface
{
    use BuildReplaceMapTrait;
    use UuidGeneratorTrait;

    private DatabaseConnectionResolver $connectionResolver;

    public function __construct(
        private ?IdentityProviderInterface $identityProvider = null,
        private ?TokenServiceInterface $tokenService = null,
        ?ContainerInterface $container = null
    ) {
        $this->connectionResolver = new DatabaseConnectionResolver($container);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     * @throws ContainerExceptionInterface
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MotLogger {
        $config = $container->get('Config');

        $motConfig = $this->resolveConfigKey($config);

        $identityProvider = null;
        $tokenService = null;

        try {
            $identityProvider = $container->get(IdentityProviderInterface::class);
        } catch (ServiceNotFoundException $exception) {
            error_log(sprintf(
                'IdentityProvider implementation not found in container for MotLoggerFactory: %s',
                $exception->getMessage(),
            ));
        }

        try {
            $tokenService = $container->get(TokenServiceInterface::class);
        } catch (ServiceNotFoundException $exception) {
            error_log(sprintf(
                'TokenService implementation not found in container for MotLoggerFactory: %s',
                $exception->getMessage(),
            ));
        }

        $factory = new self($identityProvider, $tokenService, $container);
        return $factory->create($motConfig);
    }

    /**
     * Resolves the config key, checking new key first then fall back to legacy keys.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function resolveConfigKey(array $config): array
    {
        return $config['mot_logger']
            ?? $config['DvsaApplicationLogger']
            ?? $config['DvsaLogger']
            ?? [];
    }

    /**
     * Creates a MotLogger from configuration
     *
     * @param array<string, mixed> $config The config block (mot_logger / DvsaApplicationLogger / DvsaLogger)
     * @return MotLogger
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function create(array $config): MotLogger
    {
        $handlers = $this->buildHandlers($config);

        if (empty($handlers)) {
            $handlers[] = new NoopHandler();
        }

        return new MotLogger(
            $this->createMonologLogger(
                $config,
                $handlers,
                $this->buildReplaceMap($config),
            ),
            $this->identityProvider,
            $this->tokenService,
            $this->resolveRequestUuid($config),
            $this->resolveIncludeToken($config),
        );
    }

    private function createMonologLogger(array $config, array $handlers, array $replaceMap): Logger
    {
        $logger = new Logger(
            (string) ($config['channel'] ?? 'dvsa-mot'),
            array_values($handlers),
            $this->buildProcessors($replaceMap),
        );

        if ($this->isErrorHandlerEnabled($config)) {
            ErrorHandler::register($logger);
        }

        return $logger;
    }


    /**
     * Resolves the request UUID from config, generating one if not set.
     *
     * @throws RandomException
     */
    private function resolveRequestUuid(array $config): string
    {
        $uuid = $config['request_uuid'] ?? $config['RequestUUID'] ?? null;

        if (is_string($uuid) && $uuid !== '') {
            return $uuid;
        }

        return $this->generateUuid();
    }

    private function resolveIncludeToken(array $config): bool
    {
        return (bool) ($config['include_token'] ?? false);
    }

    /**
     * Builds the logger processor list.
     *
     * @param array<string, string> $replaceMap
     * @return array<int, callable|ProcessorInterface>
     */
    private function buildProcessors(array $replaceMap): array
    {
        return empty($replaceMap)
            ? []
            : [
                new SensitiveDataProcessor($replaceMap),
                new ReplaceTraceArgsProcessor($replaceMap),
            ];
    }

    /**
     * Determines whether the error handler should be registered.
     * Supports both new key (register_error_handler) and legacy key (registerExceptionHandler)
     *
     * @param array<string, mixed> $config
     * @return bool
     */
    private function isErrorHandlerEnabled(array $config): bool
    {
        return ($config['register_error_handler'] ?? false) === true
            || ($config['registerExceptionHandler'] ?? false) === true;
    }

    /**
     * Resolves the current environment name.
     *
     * Priority:
     *  1. config['environment'] (explicit)
     *  2. $_ENV['APP_ENV') / getenv('APP_ENV')
     *  3. null (no environment awareness)
     *
     * @param array<string, mixed> $config
     * @return ?string
     */
    private function resolveEnvironment(array $config): ?string
    {
        $env = $config['environment'] ?? null;
        if (is_string($env) && $env !== '') {
            return $env;
        }

        $appEnv = null;
        if (array_key_exists('APP_ENV', $_ENV)) {
            $appEnv = $_ENV['APP_ENV'];
        } elseif (getenv('APP_ENV') !== false) {
            $appEnv = getenv('APP_ENV');
        }

        if (is_string($appEnv) && $appEnv !== '') {
            return $appEnv;
        }

        return null;
    }

    /**
     * Resolves the log level for a writer using a 3-tier priority chain.
     *
     * Priority:
     *  1. Per-writer environment array
     *  2. Per-writer fixed string
     *  3. Global environment_levels
     *  4. Default: 'debug'
     *
     * @param array<string, mixed> $writer
     * @param ?string $environment
     * @param array<string, mixed> $config
     * @return string
     */
    private function resolveWriterLevel(array $writer, ?string $environment, array $config): string
    {
        $writerLevel = $writer['level'] ?? null;

        if (is_array($writerLevel) && $environment !== null) {
            $envLevel = $writerLevel[$environment] ?? null;
            if (is_string($envLevel) && $envLevel !== '') {
                return $envLevel;
            }
        }

        if (is_string($writerLevel) && $writerLevel !== '') {
            return $writerLevel;
        }

        $envLevel = $config['environment_levels'] ?? null;
        if (is_array($envLevel) && $environment !== null) {
            $globalLevel = $envLevel[$environment] ?? null;
            if (is_string($globalLevel) && $globalLevel !== '') {
                return $globalLevel;
            }
        }

        return 'debug';
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function buildHandlers(array $config): array
    {
        $handlers = [];
        $environment = $this->resolveEnvironment($config);

        foreach (($config['writers'] ?? []) as $writer) {
            if (!is_array($writer)) {
                continue;
            }
            if (($writer['enabled'] ?? false) !== true) {
                continue;
            }

            $level = $this->resolveWriterLevel($writer, $environment, $config);
            $handler = match ($writer['type'] ?? '') {
                'stream' => $this->createStreamHandler($writer, $level),
                'database' => $this->createDatabaseHandler($writer, $level, $config),
                default => null,
            };

            if ($handler !== null) {
                $handlers[] = $handler;
            }
        }

        return $handlers;
    }

    private function createStreamHandler(array $writer, string $level): StreamHandler
    {
        $validLevels = [
            'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency',
        ];

        if (!in_array(strtolower($level), $validLevels, true)) {
            $level = 'debug';
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        $handler = new StreamHandler(
            (string) ($writer['path'] ?? 'php://stderr'),
            Logger::toMonologLevel($level)
        );

        $formatter = (string) ($writer['formatter'] ?? 'pipe');
        match ($formatter) {
            'json' => $handler->setFormatter(new JsonFormatter()),
            default => $handler->setFormatter(new PipeDelimitedFormatter()),
        };

        return $handler;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function createDatabaseHandler(
        array $writer,
        string $level,
        array $config,
    ): ?DoctrineDbalHandler {
        $validLevels = [
            'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency',
        ];

        if (!in_array(strtolower($level), $validLevels, true)) {
            $level = 'debug';
        }

        $connection = $this->connectionResolver->resolveConnection($writer, $config);

        if (!$connection instanceof Connection || !isset($writer['table'])) {
            return null;
        }

        $columnMap = $writer['column_map'] ?? null;
        if ($columnMap === null) {
            $columnMap = $this->connectionResolver->resolveLegacyColumnMap(
                (string) $writer['table'],
                $config,
            );
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        return new DoctrineDbalHandler(
            $connection,
            (string) $writer['table'],
            $columnMap,
            Logger::toMonologLevel($level)
        );
    }
}
