# DVSA MOT Logger

Unified logging utility for DVSA MOT applications, powered by Monolog.

## Overview

This library provides a unified logging solution for the DVSA MOT applications, consolidating functionality from two legacy packages:

- **dvsa/mot-logger** - The original code base stored under this repository, featuring database logging with Doctrine SQL query.
- **dvsa/mot-application-logger** - MVC event listeners for request/response/exception logging

## Features

- Monolog 3 based logging
- Multiple named loggers via Laminas `ServiceManager`
- Per-logger and per-writer configuration
- Stream and Doctrine DBAL handlers
- Environment-aware log levels
- Sensitive data masking
- Optional token inclusion in metadata
- Laminas MVC request/response/exception listeners
- Backward compatibility for legacy config keys and service names

## Requirements

- PHP 8.2+
- Monolog 3.x
- Doctrine DBAL 3.x or 4.x
- Laminas EventManager 3.15+
- Laminas MVC 3.8+
- Laminas Router 3.7+

## Installation

```bash
composer require dvsa/mot-logger
```

If you are using Laminas MVC, add the module to your application config:

```php
return [
    'modules' => [
        // ...
        'DvsaLogger',
    ],
];
```

## Custom logger quick start

If you only need the basics for a custom logger, define it under `mot_logger.loggers` and fetch it by name from the container:

```php
return [
    'mot_logger' => [
        'loggers' => [
            'default' => [
                'channel' => 'mot-app',
                'writers' => [
                    [
                        'type' => 'stream',
                        'path' => 'data/log/application.log',
                        'enabled' => true,
                    ],
                ],
            ],
            'audit_logger' => [
                'channel' => 'mot-audit',
                'writers' => [
                    [
                        'type' => 'stream',
                        'path' => 'data/log/audit.log',
                        'formatter' => 'json',
                        'enabled' => true,
                    ],
                ],
            ],
        ],
    ],
];
```

```php
$defaultLogger = $container->get(DvsaLogger\Logger\MotLogger::class);
$auditLogger = $container->get('audit_logger');
```

Use this when you want separate channels or outputs for different parts of your application. Full configuration details are in the sections below.

## Configuration

All configuration lives under the `mot_logger` root key.

### Minimal configuration

The default logger is usually configured under `mot_logger.loggers.default`:

```php
return [
    'mot_logger' => [
        'environment' => null,
        'environment_levels' => [
            'dev' => 'debug',
            'int' => 'info',
            'prv' => 'warning',
            'pre-prod' => 'error',
            'prod' => 'critical',
        ],
        'mask_credentials' => [
            'mask' => '********',
            'fields' => ['password', 'pwd', 'pass', 'secret'],
        ],
        'include_token' => false,
        'register_error_handler' => true,
        'loggers' => [
            'default' => [
                'channel' => 'my-app',
                'writers' => [
                    [
                        'type' => 'stream',
                        'path' => 'data/log/app.log',
                        'formatter' => 'pipe',
                        'enabled' => true,
                    ],
                ],
            ],
        ],
    ],
];
```

### Configuration keys

The most important keys are:

| Key                      | Type                   | Purpose                                                            |
|--------------------------|------------------------|--------------------------------------------------------------------|
| `request_uuid`           | `string or null`       | Override the generated request UUID                                |
| `register_error_handler` | `bool`                 | Register Monolog as the PHP error handler                          |
| `include_token`          | `bool`                 | Include token data in metadata                                     |
| `environment`            | `string or null`       | Explicit environment name, or `null` to auto-detect from `APP_ENV` |
| `environment_levels`     | `array<string,string>` | Global per-environment level thresholds                            |
| `mask_credentials`       | `array`                | Fields to mask before logging                                      |
| `loggers`                | `array<string,array>`  | Named logger definitions                                           |
| `listeners`              | `array`                | Column maps and switches for MVC/database listeners                |
| `doctrine_query`         | `array`                | Doctrine SQL query logging options                                 |

### Custom logger setup

MOT Logger adds support for **multiple named loggers**.

Define each logger under `mot_logger.loggers` and request it from the container by its array key.

```php
return [
    'mot_logger' => [
        'environment' => 'dev',
        'environment_levels' => [
            'dev' => 'debug',
            'prod' => 'critical',
        ],
        'mask_credentials' => [
            'mask' => '********',
            'fields' => ['password', 'secret'],
        ],
        'loggers' => [
            'default' => [
                'channel' => 'mot-app',
                'writers' => [
                    [
                        'type' => 'stream',
                        'path' => 'data/log/application.log',
                        'formatter' => 'pipe',
                        'enabled' => true,
                    ],
                ],
            ],
            'custom_logger' => [
                'channel' => 'custom-logger',
                'writers' => [
                    [
                        'type' => 'stream',
                        'path' => 'data/log/custom-logger.json',
                        'formatter' => 'json',
                        'level' => [
                            'dev' => 'info',
                            'prod' => 'warning',
                        ],
                        'enabled' => true,
                    ],
                ],
            ],
            'other_logger' => [
                'channel' => 'other-logger',
                'writers' => [
                    [
                        'type' => 'stream',
                        'path' => 'data/log/other-logger.log',
                        'formatter' => 'pipe',
                        'enabled' => true,
                    ],
                ],
            ],
        ],
    ],
];
```

#### How named logger resolution works

- `DvsaLogger\Logger\MotLogger::class` resolves to `mot_logger.loggers.default`
- any configured logger name such as `custom_logger` can be fetched directly from the container
- named logger config is merged with root-level `mot_logger` config using recursive replacement
- shared settings such as `environment_levels`, `mask_credentials`, `include_token`, and `request_uuid` apply to every named logger unless overridden

That means you can keep common settings at the top level and only specify channel/writers per logger.

### Retrieving loggers from the container

Use the default logger:

```php
use DvsaLogger\Logger\MotLogger;

$logger = $container->get(MotLogger::class);
$logger->info('Default logger message');
```

Use a named logger:

```php
$auditLogger = $container->get('custom_logger');
$auditLogger->info('Audit event', ['entityId' => 123]);
```

If a service should always receive a specific named logger, wire that explicitly in your application factory:

```php
use App\Service\AuditService;
use DvsaLogger\Logger\MotLogger;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

final class AuditServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): AuditService
    {
        /** @var MotLogger $logger */
        $logger = $container->get('custom_logger');

        return new AuditService($logger);
    }
}
```

### Writers

Each logger can have zero or more writers.

#### Stream writer

```php
[
    'type' => 'stream',
    'path' => '/var/log/app/application.log',
    'formatter' => 'pipe', // 'pipe' or 'json'
    'level' => 'info',
    'enabled' => true,
]
```

If `path` is omitted, the handler falls back to `php://stderr`.

#### Database writer

```php
[
    'type' => 'database',
    'connection' => 'doctrine.connection.mot_logger',
    'table' => 'frontend_request',
    'column_map' => [
        'timestamp' => 'timestamp',
        'priority' => 'priority',
        'priorityName' => 'priorityName',
        'message' => 'message',
        'extra' => [
            'request_uuid' => 'request_uuid',
            'username' => 'username',
            'uri' => 'uri',
        ],
    ],
    'level' => 'info',
    'enabled' => true,
]
```

`connection` may be either:

- a Doctrine DBAL `Connection` instance
- a service name that resolves to a Doctrine DBAL `Connection`

If `column_map` is omitted for a known listener table such as `frontend_request`, the library can fall back to the matching legacy listener mapping.

### Environment-aware log levels

The active environment is resolved in this order:

1. `mot_logger.environment`
2. `APP_ENV` from `$_ENV` or `getenv()`
3. no environment-specific override

For each writer, the log level is resolved in this order:

1. `writer.level[$environment]`
2. `writer.level`
3. `mot_logger.environment_levels[$environment]`
4. `debug`

Example:

```php
return [
    'mot_logger' => [
        'environment' => null,
        'environment_levels' => [
            'dev' => 'debug',
            'int' => 'info',
            'prod' => 'critical',
        ],
        'loggers' => [
            'default' => [
                'channel' => 'my-app',
                'writers' => [
                    [
                        'type' => 'stream',
                        'path' => 'data/log/app.log',
                        'level' => [
                            'dev' => 'debug',
                            'prod' => 'error',
                        ],
                        'enabled' => true,
                    ],
                ],
            ],
        ],
    ],
];
```

### Sensitive data masking

Sensitive fields are masked by processors before they are written by handlers.

```php
return [
    'mot_logger' => [
        'mask_credentials' => [
            'mask' => '****',
            'fields' => ['password', 'secret', 'token'],
        ],
    ],
];
```

### Token inclusion control

By default, authentication tokens are **not** included in metadata. Enable them only when you explicitly need that information.

```php
return [
    'mot_logger' => [
        'include_token' => true,
    ],
];
```

## Logger classes

### `MotLogger`

The main structured logger. It wraps Monolog and enriches log entries with metadata such as username, request UUID, timestamps and optional token information.

### `ConsoleLogger`

Convenience logger for console applications that writes to stdout.

### `SystemLogger`

Fallback/system logger that writes via PHP `error_log()`.

## MVC event listeners

When used in a Laminas MVC application, the module attaches listeners during `Module::onBootstrap()`.

| Listener                   | Event        | Purpose                                              |
|----------------------------|--------------|------------------------------------------------------|
| `RequestListener`          | `route`      | Capture incoming HTTP request details                |
| `ResponseListener`         | `finish`     | Log response status, content type and execution time |
| `ApiRequestListener`       | `route`      | Capture API-specific request metadata                |
| `ApiClientRequestListener` | shared event | Log outbound API client requests                     |
| `ExceptionListener`        | `route`      | Capture and log uncaught exceptions                  |

These listeners use the configured logger and listener table mappings in `mot_logger.listeners`.

## Doctrine query logging

`DvsaLogger\Service\DoctrineQueryLoggerService` can be used to log SQL query details, parameters, types and execution time.

Configuration lives under `mot_logger.doctrine_query`:

```php
return [
    'mot_logger' => [
        'doctrine_query' => [
            'enabled' => true,
            'table' => 'doctrine_query',
            'columnMap' => [
                'timestamp' => 'timestamp',
                'priority' => 'priority',
                'priorityName' => 'priorityName',
                'message' => 'message',
                'extra' => [
                    'query' => 'query',
                    'parameters' => 'parameters',
                    'types' => 'types',
                    'query_time' => 'query_time',
                    'context' => 'context',
                ],
            ],
        ],
    ],
];
```

## Backward compatibility and migration

The package still supports legacy root config keys and service aliases for existing MOT applications.

### Supported legacy root keys

The factory resolves config from the first available key in this order:

1. `mot_logger`
2. `DvsaApplicationLogger`
3. `DvsaLogger`

### Migration checklist

1. replace old package dependencies with `dvsa/mot-logger`
2. move logger config under `mot_logger`
3. update direct class references to `DvsaLogger\Logger\MotLogger` where practical
4. optionally keep legacy aliases while migrating consumers gradually

Legacy service names used by existing applications are still aliased in `config/module.config.php`.

## Architecture

```text
src/
├── Adapter/      # Integration adapters
├── Contract/     # Public interfaces
├── Debugger/     # Debug/trace helpers
├── Factory/      # Service manager factories and abstract factories
├── Formatter/    # Pipe-delimited and JSON formatters
├── Handler/      # Monolog handlers
├── Helper/       # Utility classes
├── Listener/     # Laminas MVC listeners
├── Logger/       # MotLogger, ConsoleLogger, SystemLogger
├── Processor/    # Metadata and data-masking processors
└── Service/      # Services such as Doctrine query logging
```

## Development

### Run tests

```bash
composer test
```

Coverage requires Xdebug or PCOV:

```bash
composer test:coverage
```

### Coding Standards


PHPStan and Psalm are configured for static analysis. Code style is enforced via PHP_CodeSniffer using DVSA coding standards.

```bash
composer phpcs
composer phpstan
composer psalm
```

## License

MIT License. See `LICENSE` for details.
