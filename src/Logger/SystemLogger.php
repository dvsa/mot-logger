<?php

declare(strict_types=1);

namespace DvsaLogger\Logger;

use DvsaLogger\Helper\FilteredStackTrace;
use Throwable;

/**
 * Fallback logger that writes to PHP error_log() when the main logger cannot resolve identity.
 * Recursively walks exception chains and logs each with a filtered stack trace.
 */
class SystemLogger
{
    public function __construct(
        private readonly array $replaceMap = []
    ) {
    }

    public function recursiveLogExceptionToSystemLog(Throwable $exception): void
    {
        do {
            $trace = $this->maskExceptionTrace(
                (new FilteredStackTrace())->getTraceAsString($exception)
            );
            $this->writeToErrorLog($exception->getMessage(), $trace);
            $exception = $exception->getPrevious();
        } while ($exception);
    }

    protected function writeToErrorLog(string $message, string $stackTrace): void
    {
        error_log($message . ' StackTrace: ' . $stackTrace);
    }

    private function maskExceptionTrace(string $exceptionTrace): string
    {
        $replaceFrom = array_keys($this->replaceMap);
        $replaceTo = array_values($this->replaceMap);
        if (empty($replaceFrom)) {
            return $exceptionTrace;
        }
        return str_replace($replaceFrom, $replaceTo, $exceptionTrace);
    }
}
