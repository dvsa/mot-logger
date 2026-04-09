<?php

    declare(strict_types=1);

    namespace DvsaLoggerTest\Unit\Helper;

    use Doctrine\DBAL\Connection;
    use DvsaLogger\Helper\DatabaseConnectionResolver;
    use Exception;
    use Laminas\ServiceManager\Exception\ServiceNotFoundException;
    use PHPUnit\Framework\TestCase;
    use Psr\Container\ContainerExceptionInterface;
    use Psr\Container\ContainerInterface;
    use Psr\Container\NotFoundExceptionInterface;
    use RuntimeException;

class DatabaseConnectionResolverTest extends TestCase
{
    public function testResolvesConnectionFromWriterConfig(): void
    {
        $connection = $this->createMock(Connection::class);
        $resolver = new DatabaseConnectionResolver();

        $result = $resolver->resolveConnection(
            ['connection' => $connection],
            [],
        );
        $this->assertSame($connection, $result);
    }

    public function testResolvesConnectionFromServiceName(): void
    {
        $connection = $this->createMock(Connection::class);
        $container = $this->createContainer(['db_service' => $connection]);
        $resolver = new DatabaseConnectionResolver();

        $result = $resolver->resolveConnection(
            ['connection' => $connection],
            [],
        );
        $this->assertSame($connection, $result);
    }

    public function testReturnsNullWhenServiceNotFound(): void
    {
        $container = $this->createContainer([]);
        $resolver = new DatabaseConnectionResolver($container);

        $result = $resolver->resolveConnection(
            ['connection' => 'missing_service'],
            [],
        );
        $this->assertNull($result);
    }

    public function testReturnsNullWhenNoConnectionAndNoDbConfig(): void
    {
        $resolver = new DatabaseConnectionResolver();

        $result = $resolver->resolveConnection(
            [],
            [],
        );
        $this->assertNull($result);
    }

    public function testResolvesLegacyColumnMapCanonicalFormat(): void
    {
        $resolver = new DatabaseConnectionResolver();
        $config = [
            'listeners' => [
                'frontend_request' => [
                    'column_map' => [
                        'log_message' => 'message',
                        'log_priority' => 'priority',
                    ],
                ],
            ],
        ];

        $result = $resolver->resolveLegacyColumnMap('frontend_request', $config);

        $this->assertSame([
            'log_message' => 'message',
            'log_priority' => 'priority',
        ], $result);
    }

    public function testResolvesLegacyColumnMapLegacyFormat(): void
    {
        $resolver = new DatabaseConnectionResolver();
        $config = [
            'listeners' => [
                'api_request' => [
                    'options' => [
                        'column_map' => [
                            'message' => 'message',
                            'priority' => 'priority',
                        ],
                    ],
                ],
            ],
        ];

        $result = $resolver->resolveLegacyColumnMap('api_request', $config);

        $this->assertSame([
            'message' => 'message',
            'priority' => 'priority',
        ], $result);
    }

    public function testResolvesColumnMapFromLoggersKey(): void
    {
        $resolver = new DatabaseConnectionResolver();
        $config = [
            'loggers' => [
                'api_response' => [
                    'column_map' => [
                        'status' => 'status_code',
                    ],
                ],
            ],
        ];

        $result = $resolver->resolveLegacyColumnMap('api_response', $config);
        $this->assertSame(['status' => 'status_code'], $result);
    }

    public function testReturnsNullForUnknownTable(): void
    {
        $resolver = new DatabaseConnectionResolver();

        $result = $resolver->resolveLegacyColumnMap('unknown_table', []);
        $this->assertNull($result);
    }

    public function testReturnsNullWhenNoListenerConfig(): void
    {
        $resolver = new DatabaseConnectionResolver();

        $result = $resolver->resolveLegacyColumnMap('frontend_request', []);
        $this->assertNull($result);
    }

    public function testResolvesConnectionFromServiceNameWithServiceNotFoundException(): void
    {
        $container = new class implements ContainerInterface {
            public function get(string $id): mixed
            {
                throw new ServiceNotFoundException('not found');
            }
            public function has(string $id): bool
            {
                return false;
            }
        };
            $resolver = new DatabaseConnectionResolver($container);
            $result = $resolver->resolveConnection(['connection' => 'missing'], []);
            $this->assertNull($result);
    }

    public function testResolvesConnectionFromServiceNameWithOtherException(): void
    {
        $container = new class implements ContainerInterface {
            public function get(string $id): mixed
            {
                throw new class extends Exception implements NotFoundExceptionInterface {
                };
            }
            public function has(string $id): bool
            {
                return false;
            }
        };

        $resolver = new DatabaseConnectionResolver($container);
        $result = $resolver->resolveConnection(['connection' => 'missing'], []);
        $this->assertNull($result);
    }

    public function testResolvesConnectionFromLegacyDbConfigThrows(): void
    {
        $resolver = new DatabaseConnectionResolver();
        $result = $resolver->resolveConnection([], [
            'dbConfig' => [
                'driver' => 'pdo_mysql',
                'dsn' => 'invalid',
                ]
            ]);
        $this->assertInstanceOf(Connection::class, $result);
    }

    public function testCreateConnectionFromConfigWithExtraParams(): void
    {
        $resolver = new DatabaseConnectionResolver();
        $dbConfig = [
        'driver' => 'pdo_mysql',
        'dsn' => 'mysql:dbname=testdb;host=127.0.0.1',
        'username' => 'user',
        'password' => 'pass',
        'extra_param' => 'value',
        ];
        $result = $resolver->resolveConnection([], ['dbConfig' => $dbConfig]);
        $this->assertInstanceOf(Connection::class, $result);
    }

    public function testCreateConnectionFromConfigParsesDsn(): void
    {
        $resolver = new DatabaseConnectionResolver();
        $dbConfig = [
        'driver' => 'pdo_mysql',
        'dsn' => 'mysql:dbname=testdb;host=127.0.0.1;port=3307',
        'username' => 'user',
        'password' => 'pass',
        ];
        $result = $resolver->resolveConnection([], ['dbConfig' => $dbConfig]);
        $this->assertInstanceOf(Connection::class, $result);
    }

    public function testResolveLegacyColumnMapWithOptionsColumnMapCamelCase(): void
    {
        $resolver = new DatabaseConnectionResolver();
        $config = [
            'listeners' => [
                'api_request' => [
                    'options' => [
                        'columnMap' => [
                            'foo' => 'bar',
                        ],
                    ],
                ],
            ],
        ];
        $result = $resolver->resolveLegacyColumnMap('api_request', $config);
        $this->assertSame(['foo' => 'bar'], $result);
    }

    public function testResolveLegacyColumnMapWithOptionsColumnMapSnakeCase(): void
    {
        $resolver = new DatabaseConnectionResolver();
        $config = [
            'listeners' => [
                'api_request' => [
                    'options' => [
                        'column_map' => [
                            'baz' => 'qux',
                        ],
                    ],
                ],
            ],
        ];
        $result = $resolver->resolveLegacyColumnMap('api_request', $config);
        $this->assertSame(['baz' => 'qux'], $result);
    }

    public function testReturnsNullWhenCreateConnectionFromConfigThrows(): void
    {
        $resolver = new class extends DatabaseConnectionResolver {
            protected function createConnectionFromConfig(array $dbConfig): Connection
            {
                throw new RuntimeException('fail');
            }
        };
            $result = $resolver->resolveConnection([], [
            'dbConfig' => ['driver' => 'pdo_mysql', 'dsn' => 'bad'],
            ]);
        $this->assertNull($result);
    }

    public function testResolveLegacyColumnMapReturnsNullWhenNoColumnMapOrOptions(): void
    {
        $resolver = new DatabaseConnectionResolver();
        $config = [
            'listeners' => [
                'frontend_request' => [
                    // No column_map, no options
                ],
            ],
        ];
        $result = $resolver->resolveLegacyColumnMap('frontend_request', $config);
        $this->assertNull($result);

        // missing both columnMap and column_map
        $config = [
            'listeners' => [
                'frontend_request' => [
                    'options' => [
                        'foo' => 'bar',
                    ],
                ],
            ],
        ];
        $result = $resolver->resolveLegacyColumnMap('frontend_request', $config);
        $this->assertNull($result);
    }

    private function createContainer(array $services): ContainerInterface
    {
        return new class ($services) implements ContainerInterface {
            private array $services;

            public function __construct(array $services)
            {
                $this->services = $services;
            }

            public function get(string $id): mixed
            {
                if (array_key_exists($id, $this->services)) {
                    return $this->services[$id];
                }
                throw new ServiceNotFoundException("Service not found: $id");
            }

            public function has(string $id): bool
            {
                return array_key_exists($id, $this->services);
            }
        };
    }
}
