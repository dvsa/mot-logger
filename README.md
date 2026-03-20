# DVSA MOT Logger

Unified logging utility for DVSA MOT applications, powered by Monolog.

## Overview

This library provides a unified logging solution for the DVSA MOT applications, consolidating functionality from two legacy packages:

- **dvsa/mot-logger** - The original code base stored under this repository, featuring database logging with Doctrine SQL query.
- **dvsa/mot-application-logger** - MVC event listeners for request/response/exception logging

### Features

- **Monolog-based logging** - Built on Monolog 3.x for robust logging capabilities
- **Doctrine DBAL support** - Log SQL queries to database
- **MVC Event Listeners** - Automatic request, response, and exception logging via Laminas
- **Environment-aware log levels** - Different log levels per environment (dev, int, prod, etc.)
- **Sensitive data masking** - Automatic masking of passwords and sensitive fields
- **Backward compatible** - Works with legacy service names and configurations

## Requirements

 - PHP 8.2+
 - Monolog 3.x
 - Doctrine DBAL 3.x or 4.x
 - PSR Container 1.0 or 2.0
 - Laminas EventManager 3.15+
 - Laminas HTTP 3.0+
 - Laminas MC 3.8+

## Installation

```bash
composer require dvsa/mot-logger
```

## Configuration


### Log Levels by Environment

The library supports environment-aware log levels. The environment can be set explicitly or auto-detected rom the `APP_ENV` environment
variable.

Configuration resolution order for each writer:
1. Writer-level environment-specific level
2. Writer-level fixed level
3. Global environment levels
4. Default level (debug)

```php
return [
    'mot_logger' => [
        'channel' => 'my_app',
        'environment' => 'dev',
        'environment_levels' => [
            'dev' => 'debug',
            'int' => 'ifo',
            'prod' => 'critical',
        ],
    ],
];
```

### Writers

The library supports multiple log writers including StreamHandler for console output and DatabaseWriter for database logging.

```php
'writers' => [
    [
        'type' => 'stream',
        'path' => '/var/log/app/application.log',
        'formatter' => 'pipe',
        'level' => 'info',
    ],
],
```

### Sensitive Data

Sensitive fields such as passwords, secrets, and tokens are automatically masked in log output. The mask character and fields to mask are configurable.

```php
'mot_logger' => [
    'mask_credentials' => [
        'mask' => '****',
        'fields' => ['password', 'secret', 'token'],
    ],
],
```

### Token Inclusion Control

By default, authentication tokens are NOT included in log metadata for the security purpose. For the debugging process, you can enable token logging globally:

```php
'mot_logger' => [
    'include_tokens' => true,
],
```

## Loggers

### MotLogger

The main logger class that provides structured logging with metadata support. It includes identity provider integration for tracking user
information and token service support for API authentication context.

### ConsoleLogger

Specialized logger for console applications that automatically logs to stdout at INFO level. Ideal for CLI commands and background jobs.

### SystemLogger

Logger that writes to PHP's error_log function. Suitable for system-level errors and critical application failures.

## MVC Event Listeners

The library automatically registers several listeners when used in a Laminas MVC application:

| Listener                 | Event        | Purpose                                                                                        |
|--------------------------|--------------|------------------------------------------------------------------------------------------------|
| RequestListener          | route        | Captures incoming HTTP request details including URI, method, parameters, and user information |
| ResponseListener         | finish       | Log response details including status code, content type, and execution time                   |
| ApiRequestListener       | route        | Caputres API-specific request data including authorization headers and request UUIDs           |
| ApiClientRequestListener | shared event | Logs outbound API client requests to external services                                         |
| ExceptionListener        | route        | Caputers and logs application exceptions with full stack traces                                |

Listeners are automatically attached in Module::onBootstrap() when the application is not running in console mode.

## Migration from Legacy Packages

This library consolidates two previous packages into one:

- **dvsa/mot-logger** - The original code base stored under this repository, featuring database logging with Doctrine SQL query.
- **dvsa/mot-application-logger** - MVC event listeners for request/response

### Step 1: Update Composer Dependencies

Remove the old packages:

```bash
composer remove dvsa/mot-logger dvsa/mot-application-logger
```

Install the new package:

```bash
composer require dvsa/mot-logger
```

### Step 2: Update Configuration

The root configuration key changed from `dvsa_application_logger` or `dvsa_logger` to `mot_logger`. The library will still detect and convert legacy configuration keys for backward compatibility.

```php
// Old (dvsa_application_logger)
return [
    'dvsa_application_logger' => [...],
];

// New
return [
    'mot_logger' => [...],
];
```

### Step 3: Update Class References (Optional)

The main logger class has been moved:

| Old Class                             | New Class                    |
|---------------------------------------|------------------------------|
| `DvsaLogger\Logger\Logger`            | `MotLogger\Logger\MotLogger` |
| `DvsaApplicationLogger\Logger\Logger` | `MotLogger\Logger\MotLogger` |

Legacy service names are still supported via aliases, so existing code will continue to work without changes.

### Step 4: PHP Version
Ensure your application is running PHP 8.2 or higher. This is a required minimum for the new library.

### Service Name Mapping

The library provides backward compatibility aliases for legacy service names:

| Old Service Name        | New Class                    |
|-------------------------|------------------------------|
| `DvsaLogger`            | `MotLogger\Logger\MotLogger` |
| `DvsaApplicationLogger` | `MotLogger\Logger\MotLogger` |

Legacy consuming application can continue using the old service names without modification, while new applications can and should use the new class names directly.

### Configuration Keys

Legacy configuration keys are automatically detected and converted to the new format. The new configuration uses `mot_logger` as the root key.

### New Features

- Constructor property promotion for cleaner implementation, following modern PHP practices.
- Environment auto-detection from APP_ENV environment variable, with fallback to default levels if not set.
- Per-writer log level configuration for fine-grained control.
- Token inclusion control for security/privacy compliance.
- Full PHP 8.2+ strict typing throughout the codebase for improved reliability and developer experience.

## Architecture

```
src/
├── Contract/       # Interface defining logger contracts
|── Factory/         # Service factories for DI
├── Formatter/         # Log formatters (JSON, Pipe-delimited)
├── Handler/           # onolog handlers for various outputs
├── Helper/            # Utility classes for logging support
|── Listener/            # MVC event listeners for auto-logging
├── Logger/             # Main logger classes (MotLogger, ConsoleLogger, SystemLogger)
├── Processor/         # Monolog processors for data transformation and enrichment
├── Service/           # Services such as Doctrine query logging
```

## Development

### Running Tests

```bash
composer test
```

Running tests with code coverage requires Xdebug or PCOV to be installed and enabled in your PHP environment.

```bash
composer test-coverage
```

### Coding Standards


PHPStan and Psalm are configured for static analysis. Code style is enforced via PHP_CodeSniffer using DVSA coding standards.

```bash
composer phpcs
```

```bash
composer phpstan
```

```bash
composer psalm
```

## License

MIT License. See LICENSE file for details.
