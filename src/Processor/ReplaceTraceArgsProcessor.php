<?php

declare(strict_types=1);

namespace DvsaLogger\Processor;

use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class ReplaceTraceArgsProcessor implements ProcessorInterface
{
    /** @var list<string> */
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
        if ($record->level->value >= Level::Error->value) {
            $extra = $record->extra;
            if (isset($extra['trace']) && is_array($extra['trace'])) {
                $extra['trace'] = $this->sanitizeArgsArray($extra['trace']);
            }
            if (isset($extra['params']) && is_array($extra['params'])) {
                $extra['params'] = $this->sanitizeArgs($extra['params']);
            }
            return $record->with(extra: $extra);
        }
        return $record;
    }

    private function sanitizeArgsArray(array $trace): array
    {
        foreach ($trace as &$frame) {
            if (isset($frame['args']) && is_array($frame['args'])) {
                $frame['args'] = $this->sanitizeArgs($frame['args']);
            }
        }
        return $trace;
    }

    private function sanitizeArgs(array $args): array
    {
        foreach ($args as $key => &$value) {
            if (in_array($key, $this->replaceFrom, true)) {
                $value = $this->replaceTo[array_search($key, $this->replaceFrom, true)];
            }
        }
        return $args;
    }
}
