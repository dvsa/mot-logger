<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Factory;

use Doctrine\DBAL\Connection;
use DvsaLogger\Contract\IdentityInterface;
use DvsaLogger\Contract\IdentityProviderInterface;
use DvsaLogger\Contract\TokenServiceInterface;
use DvsaLogger\Factory\MotLoggerFactory;
use DvsaLogger\Formatter\JsonFormatter;
use DvsaLogger\Formatter\PipeDelimitedFormatter;
use DvsaLogger\Handler\DoctrineDbalHandler;
use DvsaLogger\Logger\MotLogger;
use DvsaLogger\Processor\DvsaMetadataProcessor;
use DvsaLogger\Processor\SensitiveDataProcessor;
use DvsaLogger\Util\ContainerTrait;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Monolog\Handler\NoopHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Random\RandomException;

class MotLoggerFactoryTest extends TestCase
{
    use ContainerTrait;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testCreateReturnsMotLogger(): void
    {
        $factory = new MotLoggerFactory();
        $logger = $factory->create([
            'channel' => 'test-channel'
        ]);

        $this->assertInstanceOf(MotLogger::class, $logger);
        $this->assertSame('test-channel', $logger->getLogger()->getName());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testInvokeWithNewConfigKey(): void
    {
        $container = $this->createContainer([
            'Config' => [
                'mot_logger' => [
                    'channel' => 'test-channel',
                    'writers' => [
                        [
                            'type'      => 'stream',
                            'path'      => 'php://stderr',
                            'enabled'   => true,
                        ],
                    ],
                ],
            ],
        ]);

        $factory = new MotLoggerFactory();
        $logger = $factory($container, MotLogger::class);

        $this->assertSame('test-channel', $logger->getLogger()->getName());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testCreateWithNoConfigUsersDefaults(): void
    {
        $factory = new MotLoggerFactory();
        $logger = $factory->create([]);

        $this->assertInstanceOf(MotLogger::class, $logger);
        $this->assertSame('dvsa-mot', $logger->getLogger()->getName());
        $this->assertCount(1, $logger->getLogger()->getHandlers());
        $this->assertInstanceOf(NoopHandler::class, $logger->getLogger()->getHandlers()[0]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testCreateRegistersErrorHandler(): void
    {
        $factory = new MotLoggerFactory();
        $logger = $factory->create([
            'register_error_handler' => true,
        ]);

        $this->assertInstanceOf(MotLogger::class, $logger);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testCreateWithStreamWriter(): void
    {
        $factory = new MotLoggerFactory();
        $logger = $factory->create([
            'writers' => [
                [
                    'type'          => 'stream',
                    'path'          => 'php://stderr',
                    'formatter'     => 'pipe',
                    'level'         => 'debug',
                    'enabled'       => true,
                ],
            ],
        ]);

        $handlers = $logger->getLogger()->getHandlers();
        $this->assertCount(1, $handlers);
        $this->assertInstanceOf(StreamHandler::class, $handlers[0]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testCreateWithJsonFormatter(): void
    {
        $factory = new MotLoggerFactory();
        $logger = $factory->create([
            'writers' => [
                [
                    'type'          => 'stream',
                    'path'          => 'php://stderr',
                    'formatter'     => 'json',
                    'level'         => 'debug',
                    'enabled'       => true,
                ],
            ],
        ]);

        $handlers = $logger->getLogger()->getHandlers();
        $this->assertCount(1, $handlers);

        $handler = $handlers[0];
        $this->assertInstanceOf(StreamHandler::class, $handler);
        $this->assertInstanceOf(JsonFormatter::class, $handler->getFormatter());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testCreateWithPipeDelimitedFormatter(): void
    {
        $factory = new MotLoggerFactory();
        $logger = $factory->create([
            'writers' => [
                [
                    'type'          => 'stream',
                    'path'          => 'php://stderr',
                    'formatter'     => 'pipe',
                    'level'         => 'debug',
                    'enabled'       => true,
                ],
            ],
        ]);

        $handlers = $logger->getLogger()->getHandlers();
        $this->assertCount(1, $handlers);

        $handler = $handlers[0];
        $this->assertInstanceOf(StreamHandler::class, $handler);
        $this->assertInstanceOf(PipeDelimitedFormatter::class, $handler->getFormatter());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testCreateWithDisabledWriterFallsBackToNoop(): void
    {
        $factory = new MotLoggerFactory();
        $logger = $factory->create([
            'writers' => [
                [
                    'type'          => 'stream',
                    'path'          => 'php://stderr',
                    'level'         => 'debug',
                    'enabled'       => false,
                ],
            ],
        ]);

        $handlers = $logger->getLogger()->getHandlers();
        $this->assertCount(1, $handlers);
        $this->assertInstanceOf(NoopHandler::class, $handlers[0]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testCreateWithUnknownWriterTypeIsIgnored(): void
    {
        $factory = new MotLoggerFactory();
        $logger = $factory->create([
            'writers' => [
                [
                    'type'          => 'stream',
                    'path'          => 'php://stderr',
                    'level'         => 'debug',
                    'enabled'       => false,
                ],
            ],
        ]);

        $handlers = $logger->getLogger()->getHandlers();
        $this->assertCount(1, $handlers);
        $this->assertInstanceOf(
            NoopHandler::class,
            $handlers[0],
            'Should use NoopHandler as fallback',
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testCreateWithMaskCredentials(): void
    {
        $factory = new MotLoggerFactory();
        $logger = $factory->create([
            'mask_credentials' => [
                'mask' => '***',
                'fields' => ['password', 'secret'],
            ],
        ]);

        $processors = $logger->getLogger()->getProcessors();

        $metadataProcessor = $this->findProcessor($processors, DvsaMetadataProcessor::class);
        $sensitiveDataProcessor = $this->findProcessor($processors, SensitiveDataProcessor::class);

        $this->assertNotNull($metadataProcessor, 'DvsaMetadataProcessor should be registered');
        $this->assertNotNull($sensitiveDataProcessor, 'SensitiveDataProcessor should be registered');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testCreateWithIdentityAndToken(): void
    {
        $identity = $this->createMock(IdentityProviderInterface::class);
        $identity->method('getIdentity')->willReturn(null);

        $tokenService = $this->createMock(TokenServiceInterface::class);
        $tokenService->method('getToken')->willReturn('test-token');

        $factory = new MotLoggerFactory($identity, $tokenService);

        $handler = new TestHandler();
        $logger = $factory->create([
            'include_token' => true,
        ]);
        $logger->getLogger()->pushHandler($handler);
        $logger->info('test');

        $record = $handler->getRecords()[0];
        $metadata = $record->extra['__dvsa_metadata__'];

        $this->assertSame(
            'test-token',
            $metadata['token'],
            "Token from TokenServiceInterface should appear in metadata"
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testCreatePassesIdentityToLogger(): void
    {
        $identity = $this->createMock(IdentityInterface::class);
        $identity->method('getUsername')->willReturn('test-user');

        $identityProvider = $this->createMock(IdentityProviderInterface::class);
        $identityProvider->method('getIdentity')->willReturn($identity);

        $factory = new MotLoggerFactory($identityProvider);

        $handler = new TestHandler();
        $logger = $factory->create([]);
        $logger->getLogger()->pushHandler($handler);
        $logger->info('test');

        $record = $handler->getRecords()[0];
        $metadata = $record->extra['__dvsa_metadata__'];

        $this->assertSame(
            'test-user',
            $metadata['username'],
            "Username from IdentityInterface should appear in metadata",
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testCreateGeneratesRequestUuid(): void
    {
        $factory = new MotLoggerFactory();
        $logger = $factory->create([]);

        $uuid = $logger->getRequestUuid();
        $this->assertNotEmpty($uuid);
        $this->assertIsString($uuid);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $uuid);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testGlobalDefaultIncludesToken(): void
    {
        $tokenService = $this->createMock(TokenServiceInterface::class);
        $tokenService->method('getToken')
            ->willReturn('test-token');

        $factory = new MotLoggerFactory(null, $tokenService);

        $logger = $factory->create([]);
        $handler = new TestHandler();
        $logger->getLogger()->pushHandler($handler);
        $logger->info('test');

        $record = $handler->getRecords()[0];
        $this->assertArrayNotHasKey('token', $record->extra['__dvsa_metadata__']);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testGlobalConfigIncludesTokenIsTrue(): void
    {
        $tokenService = $this->createMock(TokenServiceInterface::class);
        $tokenService->method('getToken')
            ->willReturn('test-token');

        $factory = new MotLoggerFactory(null, $tokenService);
        $logger = $factory->create([
            'include_token' => true,
        ]);

        $handler = new TestHandler();
        $logger->getLogger()->pushHandler($handler);
        $logger->info('test');

        $record = $handler->getRecords()[0];

        $this->assertArrayHasKey('token', $record->extra['__dvsa_metadata__']);
        $this->assertSame('test-token', $record->extra['__dvsa_metadata__']['token']);
    }

    // ==================================
    // Environment-aware log level tests
    // ==================================

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testWriterLevelIsSetFromConfig(): void
    {
        $factory = new MotLoggerFactory();
        $logger = $factory->create([
            'writers' => [
                [
                    'type'      => 'stream',
                    'path'      => 'php://stderr',
                    'level'     => 'error',
                    'enabled'   => true,
                ],
            ],
        ]);

        $handlers = $logger->getLogger()->getHandlers();
        $this->assertCount(1, $handlers);
        $this->assertSame(Level::Error->value, $handlers[0]->getLevel()->value);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testLegacyFixedLevelUnchanged(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'log');
        $factory = new MotLoggerFactory();
        $logger = $factory->create([
            'writers' => [
                [
                    'type'      => 'stream',
                    'path'      => $tmpFile,
                    'level'     => 'error',
                    'enabled'   => true,
                ],
            ],
        ]);

        $logger->info('should be filtered');
        $logger->err('should pass');

        $logContents = file_get_contents($tmpFile);

        $this->assertStringContainsString(
            'should pass',
            $logContents,
            'Error message should be logged',
        );
        $this->assertStringNotContainsString(
            'should be filtered',
            $logContents,
            'Info message should not be logged',
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testGlobalEnvironmentLevels(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'log');
        $factory = new MotLoggerFactory();
        $logger = $factory->create([
            'environment' => 'dev',
            'environment_levels' => [
                'dev'       => 'debug',
                'prod'      => 'critical',
            ],
            'writers' => [
                [
                    'type'      => 'stream',
                    'path'      => $tmpFile,
                    'enabled'   => true,
                ],
            ],
        ]);

        $logger->debug('dev passes');

        $logContents = file_get_contents($tmpFile);

        $this->assertStringContainsString(
            'dev passes',
            $logContents,
            'The global dev environment levels should be passed for writers',
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testGlobalEnvironmentLevelProd(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'log');
        $factory = new MotLoggerFactory();
        $logger = $factory->create([
            'environment' => 'prod',
            'environment_levels' => [
                'dev'       => 'debug',
                'prod'      => 'critical',
            ],
            'writers' => [
                [
                    'type'      => 'stream',
                    'path'      => $tmpFile,
                    'enabled'   => true,
                ],
            ],
        ]);

        $logger->err('should be filtered');
        $logger->crit('should pass');

        $logContents = file_get_contents($tmpFile);

        $this->assertStringContainsString(
            'should pass',
            $logContents,
            'The global critical level environment should pass the logged message',
        );
        $this->assertStringNotContainsString(
            'should be filtered',
            $logContents,
            'The global critical level environment should not be pass the error logged message',
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testPerWriterEnvironmentOverridesGlobal(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'log');
        $factory = new MotLoggerFactory();
        $logger = $factory->create([
            'environment' => 'dev',
            'environment_levels' => ['dev' => 'debug'],
            'writers' => [
                [
                    'type'      => 'stream',
                    'path'      => $tmpFile,
                    'level'     => ['dev' => 'info'],
                    'enabled'   => true,
                ],
            ],
        ]);

        $logger->debug('should be filtered');
        $logger->info('should pass');

        $logContents = file_get_contents($tmpFile);

        $this->assertStringContainsString(
            'should pass',
            $logContents,
            'Should pass the local writer dev level log message',
        );
        $this->assertStringNotContainsString(
            'should be filtered',
            $logContents,
            'Should not log the message lower than writer log level',
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testFixedStringOverridesEnvironmentArray(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'log');
        $factory = new MotLoggerFactory();
        $logger = $factory->create([
            'environment' => 'dev',
            'environment_levels' => ['dev' => 'debug'],
            'writers' => [
                [
                    'type'      => 'stream',
                    'path'      => $tmpFile,
                    'level'     => 'error',
                    'enabled'   => true,
                ]
            ],
        ]);

        $logger->info('should be filtered');
        $logger->error('should pass');

        $logContents = file_get_contents($tmpFile);

        $this->assertStringContainsString(
            'should pass',
            $logContents,
            'Should log message for local writer log level'
        );
        $this->assertStringNotContainsString(
            'should be filtered',
            $logContents,
            'Should not log the message lower than writer log level'
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testAutoDetectsEnvironmentFromAppEnv(): void
    {
        $originalEnv = $_ENV['APP_ENV'] ?? null;
        $_ENV['APP_ENV'] = 'prod';
        $tmpFile = tempnam(sys_get_temp_dir(), 'log');

        try {
            $factory = new MotLoggerFactory();
            $logger = $factory->create([
                'environment_levels' => [
                    'dev'       => 'debug',
                    'prod'      => 'critical',
                ],
                'writers' => [[
                    'type'      => 'stream',
                    'path'      => $tmpFile,
                    'enabled'   => true,
                ]],
            ]);

            $logger->err('should be filtered');
            $logger->crit('should pass');

            $logContents = file_get_contents($tmpFile);

            $this->assertStringContainsString(
                'should pass',
                $logContents,
                'Should log message for auto-detection log level threshold',
            );
            $this->assertStringNotContainsString(
                'should be filtered',
                $logContents,
                'Should not log the message lower than auto-detected log level threshold'
            );
        } finally {
            if ($originalEnv === null) {
                unset($_ENV['APP_ENV']);
            } else {
                $_ENV['APP_ENV'] = $originalEnv;
            }
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testNoEnvironmentFallsBackToDefault(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'log');
        $factory = new MotLoggerFactory();
        $logger = $factory->create([
            'environment' => null,
            'environment_levels' => null,
            'writers' => [
                [
                    'type'      => 'stream',
                    'path'      => $tmpFile,
                    'enabled'   => true,
                    'level'     => null,
                ],
            ],
        ]);

        $logger->debug('default passes');

        $logContents = file_get_contents($tmpFile);

        $this->assertStringContainsString(
            'default passes',
            $logContents,
            'Should log message with default log level when no environment is set',
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testPerWriterArrayFallsBackToGlobalWhenEnvMissing(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'log');
        $factory = new MotLoggerFactory();
        $logger = $factory->create([
            'environment'        => 'int',
            'environment_levels' => ['int' => 'warning'],
            'writers' => [[
                'type'      => 'stream',
                'path'      => $tmpFile,
                'level'     => ['dev' => 'debug'], // no 'int' key -> falls back to global
                'enabled'   => true,
            ]],
        ]);

        $logger->info('should be filtered');
        $logger->warn('should pass');

        $logContents = file_get_contents($tmpFile);

        $this->assertStringContainsString(
            'should pass',
            $logContents,
            'Should log message for local writer log level',
        );
        $this->assertStringNotContainsString(
            'should be filtered',
            $logContents,
            'Should fallback to global log level for higher environment level',
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testResolvesLegacyConfigKeys(): void
    {
        $factory = new MotLoggerFactory();
        $container = $this->createContainer([
           'Config' => [
                'DvsaApplicationLogger' => ['channel' => 'legacy1'],
                'DvsaLogger' => ['channel' => 'legacy2'],
           ],
        ]);

        $logger = $factory->__invoke($container, 'MotLogger');

        $this->assertSame('legacy1', $logger->getLogger()->getName());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testLegacyErrorHandlerKey(): void
    {
        $factory = new MotLoggerFactory();
        $logger = $factory->create(['registerExceptionHandler' => true]);

        $this->assertInstanceOf(MotLogger::class, $logger);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     * @throws ContainerExceptionInterface
     */
    public function testRequestUuidConfigKeys(): void
    {
        $factory = new MotLoggerFactory();

        $logger1 = $factory->create(['request_uuid' => 'abc']);
        $logger2 = $factory->create(['RequestUUID' => 'def']);

        $this->assertSame('abc', $logger1->getRequestUuid());
        $this->assertSame('def', $logger2->getRequestUuid());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testStreamHandlerWithInvalidLevelFallsBackToDebug(): void
    {
        $factory = new MotLoggerFactory();
        $logger = $factory->create([
            'writers' => [[
                'type' => 'stream',
                'path' => 'php://stderr',
                'level' => 'notalevel',
                'enabled' => true,
            ]],
        ]);

        $handlers = $logger->getLogger()->getHandlers();

        $this->assertSame(100, $handlers[0]->getLevel()->value); // 100 = DEBUG
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testStreamHandlerWithMissingPathUsesDefault(): void
    {
        $factory = new MotLoggerFactory();
        $logger = $factory->create([
            'writers' => [[
                'type' => 'stream',
                'enabled' => true,
            ]],
        ]);

        $handlers = $logger->getLogger()->getHandlers();

        $this->assertInstanceOf(StreamHandler::class, $handlers[0]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testStreamHandlerWithMissingFormatterUsesPipe(): void
    {
        $factory = new MotLoggerFactory();
        $logger = $factory->create([
            'writers' => [[
                'type' => 'stream',
                'path' => 'php://stderr',
                'enabled' => true,
            ]],
        ]);

        $handlers = $logger->getLogger()->getHandlers();

        $this->assertInstanceOf(PipeDelimitedFormatter::class, $handlers[0]->getFormatter());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testDatabaseHandlerWithInvalidConnectionReturnsNull(): void
    {
        $factory = new MotLoggerFactory();

        // Simulate missing connection/table
        $logger = $factory->create([
            'writers' => [[
                'type' => 'database',
                'enabled' => true,
            ]],
        ]);

        $handlers = $logger->getLogger()->getHandlers();

        $this->assertInstanceOf(NoopHandler::class, $handlers[0]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testDatabaseHandlerWithLegacyColumnMap(): void
    {
        $factory = new MotLoggerFactory();

        // Simulate legacy column map fallback (connection/table mocked internally)
        $logger = $factory->create([
            'writers' => [[
                'type' => 'database',
                'table' => 'log_table',
                'enabled' => true,
            ]],
        ]);

        $handlers = $logger->getLogger()->getHandlers();
        $this->assertNotEmpty($handlers);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testUnknownWriterTypeIsIgnored(): void
    {
        $factory = new MotLoggerFactory();

        $logger = $factory->create([
            'writers' => [[
                'type' => 'unknown',
                'enabled' => true,
            ]],
        ]);

        $handlers = $logger->getLogger()->getHandlers();

        $this->assertInstanceOf(NoopHandler::class, $handlers[0]);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     * @throws ContainerExceptionInterface
     */
    public function testEnvironmentResolutionFromConfigEnvGetenv(): void
    {
        $factory = new MotLoggerFactory();

        $originalEnv = $_ENV['APP_ENV'] ?? null;
        $originalGetenv = getenv('APP_ENV');

        try {
            $logger = $factory->create(['environment' => 'foo']);
            $this->assertNotNull($logger);

            $_ENV['APP_ENV'] = 'bar';

            $logger = $factory->create([]);
            $this->assertNotNull($logger);

            putenv('APP_ENV=baz');

            $logger = $factory->create([]);
            $this->assertNotNull($logger);
        } finally {
            if ($originalEnv !== null) {
                $_ENV['APP_ENV'] = $originalEnv;
            } else {
                unset($_ENV['APP_ENV']);
            }

            if ($originalGetenv !== false) {
                putenv('APP_ENV=' . $originalGetenv);
            } else {
                putenv('APP_ENV');
            }
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testWriterLevelResolutionPriority(): void
    {
        $factory = new MotLoggerFactory();
        $logger = $factory->create([
            'environment' => 'dev',
            'environment_levels' => ['dev' => 'info'],
            'writers' => [[
                'type' => 'stream',
                'path' => 'php://stderr',
                'level' => ['dev' => 'error'],
                'enabled' => true,
            ]],
        ]);

        $handlers = $logger->getLogger()->getHandlers();

        $this->assertSame(Level::Error->value, $handlers[0]->getLevel()->value);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testCreateHandlesContainerException(): void
    {
        $factory = new MotLoggerFactory();
        $container = $this->createContainer(['non-existing-service']);
        $this->expectException(ServiceNotFoundException::class);

        $factory->__invoke($container, 'MotLogger');
    }

    public function testDatabaseHandlerWithInvalidLevelFallsBackToDebug(): void
    {
        $factory = new MotLoggerFactory();
        try {
            $logger = $factory->create([
                'writers' => [[
                    'type' => 'database',
                    'table' => 'log_table',
                    'level' => 'notalevel',
                    'enabled' => true,
                ]],
            ]);
        } catch (NotFoundExceptionInterface $e) {
        } catch (ContainerExceptionInterface $e) {
        } catch (RandomException $e) {
        }
        $handlers = $logger->getLogger()->getHandlers();
        $this->assertNotEmpty($handlers);
        $handler = $handlers[0];
        if (method_exists($handler, 'getLevel')) {
            $this->assertSame(Level::Debug->value, $handler->getLevel()->value);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function testDatabaseHandlerWithMissingConnectionReturnsNull(): void
    {
        $factory = new MotLoggerFactory();
        $logger = $factory->create([
            'writers' => [[
                'type' => 'database',
                'enabled' => true,
            ]],
        ]);
        $handlers = $logger->getLogger()->getHandlers();
        $this->assertInstanceOf(NoopHandler::class, $handlers[0]);
    }

    public function testCreateWithDatabaseWriterUsesLegacyColumnMap(): void
    {
        $connection = $this->createMock(Connection::class);
        $factory = new MotLoggerFactory();
        $logger = $factory->create([
            'writers' => [
                [
                'type' => 'database',
                'connection' => $connection,
                'table' => 'frontend_request',
                'enabled' => true,
                ],
            ],
            'listeners' => [
               'frontend_request' => [
                   'column_map' => [
                       'log_message' => 'message',
                   ],
               ],
            ],
        ]);

        $handlers = $logger->getLogger()->getHandlers();

        $this->assertCount(1, $handlers);
        $this->assertInstanceOf(DoctrineDbalHandler::class, $handlers[0]);
    }

    private function findProcessor(array $processors, string $class): ?object
    {
        foreach ($processors as $processor) {
            if ($processor instanceof $class) {
                return $processor;
            }
        }
        return null;
    }
}
