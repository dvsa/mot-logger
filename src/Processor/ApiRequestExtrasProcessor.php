<?php

declare(strict_types=1);

namespace DvsaLogger\Processor;

use DvsaLogger\Listener\RequestListener;
use Monolog\LogRecord;

/**
 * Monolog processor that enriches log records for API requests.
 * This processor is deprecated and will be removed in a future release.
 *
 * @deprecated Use DvsaLogger\Listener\RequestListener instead, which embeds context inline.
 * @see RequestListener
 */
readonly class ApiRequestExtrasProcessor
{
    public function __construct(
        private array $requestExtras = []
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $extras = [
            'uri'                   => $this->requestExtras['uri'] ?? '',
            'parameters'            => $this->requestExtras['parameters'] ?? '',
            'request_method'        => $this->requestExtras['request_method'] ?? '',
            'ip_address'            => $this->requestExtras['ip_address'] ?? '',
            'php_session_id'        => $this->requestExtras['php_session_id'] ?? '',
            'route'                 => $this->requestExtras['route'] ?? '',
            'api_request_uuid'      => $this->requestExtras['api_request_uuid'] ?? '',
            'frontend_request_uuid' => $this->requestExtras['frontend_request_uuid'] ?? '',
            'token'                 => $this->requestExtras['token'] ?? '',
            'user_agent'            => $this->requestExtras['user_agent'] ?? '',
        ];

        return $record->with(extra: array_merge($record->extra, $extras));
    }
}
