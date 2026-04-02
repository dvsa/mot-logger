<?php

declare(strict_types=1);

namespace DvsaLogger\Formatter;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\JsonFormatter as BaseJsonFormatter;
use Monolog\LogRecord;
use RuntimeException;

/**
 * Custom JSON formatter for DVSA Logger.
 */
class JsonFormatter implements FormatterInterface
{
    /** @var list<string>  */
    private const FIELD_ORDER = [
        'microtimeTimestamp',
        'timestamp',
        'priority',
        'priorityName',
        'level',
        'logEntryType',
        'username',
        'token',
        'traceId',
        'parentSpanId',
        'spanId',
        'callerName',
        'logger_name',
        'exceptionType',
        'message',
        'extra',
        'stacktrace',
    ];

    public function format(LogRecord $record): string
    {
        $json = json_encode($this->extractFields($record));

        if ($json === false) {
            throw new RuntimeException(
                'Failed to encode log record to JSON: ' . json_last_error_msg()
            );
        }

        return $json;
    }

    public function formatBatch(array $records): string
    {
        $output = '';
        foreach ($records as $record) {
            $output .= $this->format($record);
        }
        return $output;
    }

    private function extractFields(LogRecord $record): array
    {
        $extra = $record->extra;
        $metadata = $extra['__dvsa_metadata__'] ?? [];

        $output = [];
        foreach (self::FIELD_ORDER as $field) {
            switch ($field) {
                case 'priority':
                    $output['priority'] = $record->level->value;
                    break;
                case 'priorityName':
                    $output['priorityName'] = strtoupper($record->level->name);
                    break;
                case 'message':
                    $output['message'] = $record->message;
                    break;
                case 'extra':
                    $userExtras = array_filter(
                        $extra,
                        static fn (mixed $key): bool => $key !== '__dvsa_metadata__', ARRAY_FILTER_USE_KEY,
                    );

                    if (!empty($userExtras)) {
                        $output['extra'] = $userExtras;
                    }
                    break;
                default:
                    if (isset($metadata[$field])) {
                        $output[$field] = $metadata[$field];
                    }
                    break;
            }
        }

        return $output;
    }
}
