<?php

declare(strict_types=1);

namespace DvsaLogger\Processor;

use Monolog\Level;
use Monolog\LogRecord;

class SensitiveDataProcessor
{
    /** @var list<string>  */
    private array $replaceFrom;
    /** @var list<string> */
    private array $replaceTo;

    public function __construct(array $replaceMap)
    {
        $this->replaceFrom = array_keys($replaceMap);
        $this->replaceTo = array_values($replaceMap);
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        if (empty($this->replaceFrom)) {
            return $record;
        }

        if ($record->level->value < Level::Error->value) {
            return $record;
        }

        $extra = $record->extra;
        if (isset($extra['trace']) && is_array($extra['trace']) && !empty($extra['trace'])) {
            $trace = $extra['trace'];
            $this->processTraceArray($trace);
            $extra['trace'] = $trace;
            return $record->with(extra: $extra);
        }

        $context = $record->context;
        if (isset($context['trace']) && is_array($context['trace']) && !empty($context['trace'])) {
            $trace = $context['trace'];
            $this->processTraceArray($trace);
            $context['trace'] = $trace;
            return $record->with(context: $context);
        }

        return $record;
    }

    /**
     * @param array $trace
     */
    private function processTraceArray(array &$trace): void
    {
        $replaceFrom = $this->replaceFrom;
        $replaceTo = $this->replaceTo;
        array_walk_recursive($trace, function (mixed &$value) use ($replaceFrom, $replaceTo) {
            if (is_string($value)) {
                $value = str_replace($replaceFrom, $replaceTo, $value);
            }
        });
    }
}
