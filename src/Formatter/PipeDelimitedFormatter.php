<?php

declare(strict_types=1);

namespace DvsaLogger\Formatter;

use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;

/**
 * Formats log records as pipe-delimited strings prefixed with ^^*.
 * Backward compatible with the original DVSA General/Error formatter output.
 */
class PipeDelimitedFormatter implements FormatterInterface
{
    private string $logFieldDelimiter = '||';
    private string $logEntryPrefix = '^^*';

    public function __construct(
        private readonly bool $includeExceptionFields = false,
    ) {
    }

    public function format(LogRecord $record): string
    {
        $data = $this->extractFields($record);
        return $this->logEntryPrefix . implode($this->logFieldDelimiter, $data);
    }

    public function formatBatch(array $records): string
    {
        $output = '';
        foreach ($records as $record) {
            $output .= $this->format($record);
        }
        return $output;
    }

    /**
     * @param LogRecord $record
     * @return array<int, string|int>
     */
    private function extractFields(LogRecord $record): array
    {
        /** @var array<string, mixed> $extra */
        $extra = $record->extra;
        /** @var array<string, mixed> $metadata */
        $metadata = $extra['__dvsa_metadata__'] ?? [];

        $fields = [
            (string) ($metadata['microtimeTimestamp'] ?? ''),
            $record->level->value,
            strtoupper($record->level->name),
            (string) ($metadata['logEntryType'] ?? 'General'),
            (string) ($metadata['username'] ?? ''),
            (string) ($metadata['token'] ?? ''),
            (string) ($metadata['traceId'] ?? ''),
            (string) ($metadata['parentSpanId'] ?? ''),
            (string) ($metadata['spanId'] ?? ''),
            (string) ($metadata['callerName'] ?? ''),
        ];

        if ($this->includeExceptionFields) {
            $fields[] = (string) ($metadata['exceptionType'] ?? '');
            $fields[] = (string) ($metadata['errorCode'] ?? '');
        }

        $fields[] = $record->message;

        $userExtras = array_filter(
            $extra,
            static fn (mixed $key): bool => $key !== '__dvsa_metadata__',
            ARRAY_FILTER_USE_KEY,
        );
        $fields[] = !empty($userExtras) ? json_encode($userExtras) : '';

        if ($this->includeExceptionFields) {
            $stacktrace = $metadata['stacktrace'] ?? '';
            if (is_array($stacktrace)) {
                $stacktrace = json_encode($stacktrace);
            }
            $fields[] = (string) $stacktrace;
        }

        return $fields;
    }
}
