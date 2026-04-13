<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Helper;

use DvsaLogger\Helper\ConfigResolutionTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

class ConfigResolutionTraitTest extends TestCase
{
    public function testResolvesNewConfigKey(): void
    {
        $container = $this->createContainer([
            'Config' => [
                'mot_logger' => ['channel' => 'test'],
                'DvsaApplicationLogger' => ['channel' => 'legacy_1'],
                'DvsaLogger' => ['channel' => 'legacy_2'],
            ]
        ]);

        $result = $this->createTestClass()->callResolveMotConfig($container);

        $this->assertSame(['channel' => 'test'], $result);
    }

    public function testFallsBackToDvsaApplicationLogger(): void
    {
        $container = $this->createContainer([
            'Config' => [
                'DvsaApplicationLogger' => ['channel' => 'legacy1'],
                'DvsaLogger' => ['channel' => 'legacy2'],
            ]
        ]);

        $result = $this->createTestClass()->callResolveMotConfig($container);

        $this->assertSame(['channel' => 'legacy1'], $result);
    }

    public function testFallsBackToDvsaLogger(): void
    {
        $container = $this->createContainer([
            'Config' => [
                'DvsaLogger' => ['channel' => 'legacy2'],
            ]
        ]);

        $result = $this->createTestClass()->callResolveMotConfig($container);

        $this->assertSame(['channel' => 'legacy2'], $result);
    }

    public function testReturnsEmptyArrayWhenNoConfigurationFound(): void
    {
        $container = $this->createContainer([
            'Config' => [
                'other_config' => ['key' => 'value'],
            ],
        ]);

        $result = $this->createTestClass()->callResolveMotConfig($container);

        $this->assertEmpty($result);
    }

    private function createContainer(array $config): ContainerInterface
    {
        return new class ($config) implements ContainerInterface {
            public function __construct(private readonly array $config)
            {
            }

            public function get(string $id): array
            {
                if ($id === 'Config') {
                    return $this->config['Config'];
                }
                throw new RuntimeException("Service not found: $id");
            }

            public function has(string $id): bool
            {
                return isset($this->config[$id]);
            }
        };
    }

    private function createTestClass(): object
    {
        return new class {
            use ConfigResolutionTrait;

            public function callResolveMotConfig(ContainerInterface $container): array
            {
                return $this->resolveMotConfig($container);
            }
        };
    }
}
