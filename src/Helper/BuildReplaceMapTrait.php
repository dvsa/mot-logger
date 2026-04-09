<?php

declare(strict_types=1);

namespace DvsaLogger\Helper;

trait BuildReplaceMapTrait
{
    /**
     * Builds the credential-replacement map from config.
     * Supports both new key (mask_credentials.field) and
     * legacy key (maskDatabaseCredentials2.argsToMask).
     *
     * @param array<string, mixed> $config
     * @return array<string, string>
     */
    private function buildReplaceMap(array $config): array
    {
        $maskConfig = $config['mask_credentials']
            ?? $config['maskDatabaseCredentials2']
            ?? $config['maskDatabaseCredentials']
            ?? null;

        if (!is_array($maskConfig)) {
            return [];
        }

        $mask = (string) ($maskConfig['mask'] ?? '********');
        $fields = $maskConfig['fields']
            ?? $maskConfig['argsToMask']
            ?? [];

        $replaceMap = [];
        foreach ((array) $fields as $field) {
            if (is_string($field)) {
                $replaceMap[$field] = $mask;
            }
        }

        return $replaceMap;
    }
}
