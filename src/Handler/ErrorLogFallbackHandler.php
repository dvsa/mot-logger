<?php

declare(strict_types=1);

namespace DvsaLogger\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use Throwable;

/**
 * Monolog handler that writes to PHP's error_log() as a fallback mechanism.
 */
class ErrorLogFallbackHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        $message = $record->message;
        $stacktrace = '';

        if (!empty($record->extra['stacktrace'])) {
            $trace = $record->extra['stacktrace'];
            if (!is_string($trace)) {
                // Safely convert non-string stacktrace to string
                $trace = print_r($trace, true);
            }
            $stacktrace = ' Stacktrace: ' . $trace;
        } elseif (($exception = $record->context['exception'] ?? null) instanceof Throwable) {
            $stacktrace = ' Stacktrace: ' . $exception->getTraceAsString();
        }

        error_log($message . $stacktrace);
    }
}
