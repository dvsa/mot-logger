<?php

declare(strict_types=1);

namespace DvsaLogger\Processor;

use Monolog\LogRecord;

/**
 * Monolog processor that enriches log records for API requests.
 * This processor is deprecated and will be removed in a future release.
 *
 * @deprecated Use DvsaLogger\Listener\ResponseListener instead, which embeds context inline.
 * @see ResponseListener
 */
readonly class ApiResponseExtrasProcessor
{
    public function __construct(
        private array $responseExtras = []
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $requestStartTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);

        $extras = [
            'status_code' => $this->responseExtras['status_code'] ?? '',
            'content_type' => $this->responseExtras['content_type'] ?? '',
            'response_content' => $this->responseExtras['response_content'] ?? '',
            'api_request_uuid' => $this->responseExtras['api_request_uuid'] ?? '',
            'frontend_request_uuid' => $this->responseExtras['frontend_request_uuid'] ?? '',
            'token' => $this->responseExtras['token'] ?? '',
            'execution_time' => microtime(true) - $requestStartTime,
        ];

        return $record->with(extra: array_merge($record->extra, $extras));
    }
}
