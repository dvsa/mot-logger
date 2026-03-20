<?php

declare(strict_types=1);

namespace DvsaLogger\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class DatabaseConnectionResolver
{
    public function __construct(
        private readonly ?ContainerInterface $container = null
    ) {
    }

    /**
     * Resolves database connection from writer config, service name, or legacy dbConfig.
     *
     * Priority:
     *  1. $writer['connection'] as service name
     *  2. $writer['connection' as Connection instance
     *  3. Legacy $config['dbConfig] from DvsaLogger config
     *
     * @param array<string, mixed> $writer Writer config array
     * @param array<string, mixed> $config Full DvsaLogger config array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function resolveConnection(array $writer, array $config): ?Connection
    {
        $connectionRef = $writer['connection'] ?? null;

        if ($connectionRef !== null) {
            if ($connectionRef instanceof Connection) {
                return $connectionRef;
            }

            if (is_string($connectionRef) && $this->container !== null) {
                try {
                    return $this->container->get($connectionRef);
                } catch (ServiceNotFoundException) {
                }
            }
        }

        // Fallback for legacy dbConfig
        $dbConfig = $config['dbConfig'] ?? null;
        if (is_array($dbConfig) && !empty($dbConfig)) {
            try {
                return $this->createConnectionFromConfig($dbConfig);
            } catch (Throwable) {
            }
        }

        return null;
    }

    /**
     * Resolve column map from legacy listener config structure.
     *
     * Canonical format:
     *      DvsaLogger.listeners.frontend_request.column_map
     *
     * Legacy fallback (for external consuming apps):
     *      DvsaLogger.listeners.frontend_request.options.columnMap
     *
     * @param string $table Table name to look up
     * @param array<string, mixed> $config Full mot_logger config
     * @return ?array<string, string|array<string, string>>
     */
    public function resolveLegacyColumnMap(string $table, array $config): ?array
    {
        $listenerKey = match ($table) {
            'frontend_request' => 'frontend_request',
            'api_request' => 'api_request',
            'api_response' => 'api_response',
            'api_client_request' => 'api_client_request',
            default => null,
        };

        if ($listenerKey === null) {
            return null;
        }

            /** @var array<string, mixed> $listeners */
            $listeners = $config['listeners'] ?? $config['loggers'] ?? [];
            /** @var array<string, mixed>|null $listenerConfig */
            $listenerConfig = $listeners[$listenerKey] ?? null;

        if ($listenerConfig === null) {
            return null;
        }

            $columnMap = $listenerConfig['column_map'] ?? null;
        if (is_array($columnMap)) {
            return $columnMap;
        }

            // Legacy fallback
            $options = $listenerConfig['options'] ?? null;
        if (is_array($options)) {
            return $options['columnMap'] ?? $options['column_map'] ?? null;
        }
        return null;
    }

    /**
     * Creates a Doctrine DBAL Connection from legacy inline config.
     *
     * Expected format (from DvsaLogger):
     *      'dbConfig' => [
     *          'driver'    => 'Pdo',
     *          'dsn'       => 'mysql:dbname=dvsa_logger;host=localhost',
     *          'username'  => 'root',
     *          'password'  => 'password',
     *      ]
     *
     * @param array<string, mixed> $dbConfig
     * @throws Exception
     */
    protected function createConnectionFromConfig(array $dbConfig): Connection
    {
        $driver = (string) ($dbConfig['driver'] ?? 'pdo_mysql');
        $dsn = $dbConfig['dsn'] ?? '';
        $username = $dbConfig['username'] ?? '';
        $password = $dbConfig['password'] ?? '';
        $dbname = '';
        $host = 'localhost';
        $port = 3306;

        if ($dsn !== '') {
            $parts = explode(';', $dsn);
            foreach ($parts as $part) {
                if (str_starts_with($part, 'dbname=')) {
                    $dbname = substr($part, 7);
                } elseif (str_starts_with($part, 'host=')) {
                    $host = substr($part, 5);
                } elseif (str_starts_with($part, 'port=')) {
                    $port = (int) substr($part, 5);
                }
            }
        }

        $params = [
            'driver' => $driver,
            'host' => $host,
            'port' => $port,
            'dbname' => $dbname,
            'user' => $username,
            'password' => $password,
        ];

        // Apply any additional params from config
        foreach ($dbConfig as $key => $value) {
            if (!in_array($key, ['driver', 'dsn', 'username', 'password'], true)) {
                $params[$key] = $value;
            }
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        return DriverManager::getConnection($params);
    }
}
