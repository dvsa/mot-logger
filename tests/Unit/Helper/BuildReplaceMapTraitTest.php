<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Helper;

use DvsaLogger\Helper\BuildReplaceMapTrait;
use PHPUnit\Framework\TestCase;

class BuildReplaceMapTraitTest extends TestCase
{
    public function testBuildsFromNewConfig(): void
    {
        $class = $this->createTestClass([
            'mask_credentials' => [
                'mask' => '***',
                'fields' => ['password', 'secret'],
            ],
        ]);
        $this->assertSame(
            ['password' => '***', 'secret' => '***'],
            $class->callBuildReplaceMap(),
        );
    }

    public function testBuildsFromLegacyConfig(): void
    {
        $class = $this->createTestClass([
            'maskDatabaseCredentials2' => [
                'mask' => '***',
                'argsToMask' => ['apiKey', 'token'],
            ],
        ]);
        $this->assertSame(
            ['apiKey' => '***', 'token' => '***'],
            $class->callBuildReplaceMap(),
        );
    }

    public function testBuildsFromOldestLegacyConfig(): void
    {
        $class = $this->createTestClass([
            'maskDatabaseCredentials' => [
                'fields' => ['password'],
            ],
        ]);
        $result = $class->callBuildReplaceMap();
        $this->assertSame(['password' => '********'], $result);
    }

    public function testUsesDefaultMaskWhenNotProvided(): void
    {
        $class = $this->createTestClass([
            'mask_credentials' => [
                'fields' => ['password'],
            ],
        ]);

        $result = $class->callBuildReplaceMap();
        $this->assertSame('********', $result['password']);
    }

    public function testReturnsEmptyArrayWhenNoConfig(): void
    {
        $class = $this->createTestClass([]);
        $this->assertSame([], $class->callBuildReplaceMap());
    }

    public function testReturnsEmptyArrayWhenFieldsEmpty(): void
    {
        $class = $this->createTestClass([
            'mask_credentials' => [
                'mask' => '***',
                'fields' => [],
            ],
        ]);
        $this->assertSame([], $class->callBuildReplaceMap());
    }

    public function testSkipsNonStringFields(): void
    {
        $class = $this->createTestClass([
            'mask_credentials' => [
                'fields' => ['password', 123, null, 'secret'],
            ],
        ]);
        $this->assertSame(
            ['password' => '********', 'secret' => '********'],
            $class->callBuildReplaceMap(),
        );
    }

    private function createTestClass(array $config): object
    {
        return new class ($config) {
            use BuildReplaceMapTrait;

            private array $config;

            public function __construct(array $config)
            {
                $this->config = $config;
            }

            public function callBuildReplaceMap(): array
            {
                return $this->buildReplaceMap($this->config);
            }
        };
    }
}
