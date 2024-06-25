<?php

namespace DvsaLogger\Service;

use PHPUnit\Framework\TestCase;
use DvsaLogger\Service\DatabaseConfigurationService;

class DatabaseConfigurationServiceTest extends TestCase
{
    public function testItReturnsNullIfNoListeners(): void
    {
        $this->assertNull(DatabaseConfigurationService::getTableName([]));
    }

    public function testItReturnsNullIfNoApiClientRequest(): void
    {
        $this->assertNull(
            DatabaseConfigurationService::getTableName(
                [
                    'listeners' => []
                ]
            )
        );
    }

    public function testItReturnsNullIfListenersIsNotArray(): void
    {
        $this->assertNull(
            DatabaseConfigurationService::getTableName(
                [
                    'listeners' => 'Not an array'
                ]
            )
        );
    }

    public function testItReturnsNullIfNoOptions(): void
    {
        $this->assertNull(
            DatabaseConfigurationService::getTableName(
                [
                    'listeners' => [
                        'api_client_request' => []
                    ]
                ]
            )
        );
    }

    public function testItReturnsNullIfApiClientRequestIsNotAnArray(): void
    {
        $this->assertNull(
            DatabaseConfigurationService::getTableName(
                [
                    'listeners' => [
                        'api_client_request' => 'Not an array'
                    ]
                ]
            )
        );
    }

    public function testItReturnsNullIfNoTableName(): void
    {
        $this->assertNull(
            DatabaseConfigurationService::getTableName(
                [
                    'listeners' => [
                        'api_client_request' => [
                            'options' => []
                        ]
                    ]
                ]
            )
        );
    }

    public function testItReturnsNullIfOptionsIsNotAnArray(): void
    {
        $this->assertNull(
            DatabaseConfigurationService::getTableName(
                [
                    'listeners' => [
                        'api_client_request' => [
                            'options' => 'Not an array'
                        ]
                    ]
                ]
            )
        );
    }

    public function testItReturnsTableNameWhenAvailable(): void
    {
        $this->assertEquals(
            'MyTable',
            DatabaseConfigurationService::getTableName(
                [
                    'listeners' => [
                        'api_client_request' => [
                            'options' => [
                                'tableName' => 'MyTable'
                            ]
                        ]
                    ]
                ]
            )
        );
    }

    public function testItReturnsColumnMapWhenAvailable(): void
    {
        $this->assertEquals(
            [
                'column1' => 'map1'
            ],
            DatabaseConfigurationService::getColumnMap(
                [
                    'listeners' => [
                        'api_client_request' => [
                            'options' => [
                                'columnMap' => [
                                    'column1' => 'map1'
                                ]
                            ]
                        ]
                    ]
                ]
            )
        );
    }
}
