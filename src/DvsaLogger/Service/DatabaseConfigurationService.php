<?php

namespace DvsaLogger\Service;

class DatabaseConfigurationService
{
    /**
     * @return string | null
     */
    public static function getTableName(array $config)
    {
        /** @var string | null */
        return DatabaseConfigurationService::getFromOptions($config, 'tableName');
    }

    /**
     * @return array | null
     */
    public static function getColumnMap(array $config)
    {
        /** @var array | null */
        return DatabaseConfigurationService::getFromOptions($config, 'columnMap');
    }

    /**
     * @return mixed
     */
    private static function getFromOptions(array $config, string $propName)
    {
        if (
            array_key_exists('listeners', $config) &&
            is_array($config['listeners']) &&
            array_key_exists('api_client_request', $config['listeners']) &&
            is_array($config['listeners']['api_client_request']) &&
            array_key_exists('options', $config['listeners']['api_client_request']) &&
            is_array($config['listeners']['api_client_request']['options']) &&
            array_key_exists($propName, $config['listeners']['api_client_request']['options'])
        ) {
            /** @var mixed */
            return $config['listeners']['api_client_request']['options'][$propName];
        }

        return null;
    }
}
