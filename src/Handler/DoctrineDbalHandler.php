<?php

declare(strict_types=1);

namespace DvsaLogger\Handler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

/**
 * Monolog handler that writes enriched log records to a MySQL database table
 * using Doctrine DBAL.
 */
class DoctrineDbalHandler extends AbstractProcessingHandler
{
    public function __construct(
        private readonly Connection $connection,
        private readonly string $tableName,
        private readonly ?array $columnMap = null,
        Level $level = Level::Debug,
    ) {
        parent::__construct($level, true);
    }

    /**
     * @throws Exception
     */
    protected function write(LogRecord $record): void
    {
        $data = $this->extractData($record);
        $this->connection->insert($this->tableName, $data);
    }

    private function extractData(LogRecord $record): array
    {
        $metadata = $record->extra['__dvsa_metadata__'] ?? [];
        $priorityName = is_array($metadata) && isset($metadata['level']) && is_string($metadata['level'])
            ? $metadata['level']
            : strtoupper($record->level->name);

        $data = [
            'timestamp' => $record->datetime->format('Y-m-d H:i:s.u'),
            'priority' => $record->level->value,
            'priorityName' => $priorityName,
            'message' => $record->message,
        ];

        $extra = [];
        foreach ($record->extra as $key => $value) {
            if (is_array($value)) {
                $encoded = json_encode($value);
                $extra[$key] = $encoded === false ? '[]' : $encoded;
            } else {
                $extra[$key] = $value;
            }
        }

        $lookup = array_merge($data, $extra);

        if ($this->columnMap !== null) {
            $mapped = [];

            foreach ($this->columnMap as $dbColumn => $sourceField) {
                if (is_array($sourceField)) {
                    $extraKey = array_key_first($sourceField);
                    if ($extraKey === null) {
                        $mapped[$dbColumn] = null;
                    } else {
                        $extraField = $sourceField[$extraKey];
                        $mapped[$dbColumn] = $extra[$extraField][$extraField] ?? null;
                    }
                } else {
                    $mapped[$dbColumn] = $lookup[$sourceField] ?? null;
                }
            }
            return $mapped;
        }

        return $lookup;
    }
}
