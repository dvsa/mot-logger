<?php

declare(strict_types=1);

namespace DvsaLogger\Processor;

use Monolog\LogRecord;

readonly class DoctrineQueryExtrasProcessor
{
    public function __construct(
        private array $requestExtras = [],
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $extras = [
          'api_endpoint_uri'        => $this->requestExtras['api_endpoint_uri'] ?? '',
          'api_query_string'        => $this->requestExtras['api_query_string'] ?? '',
          'api_post_data'           => $this->requestExtras['api_post_data'] ?? '',
            'api_method'            => $this->requestExtras['api_method'] ?? '',
            'ip'                    => $this->requestExtras['ip'] ?? '',
            'session_id'            => $this->requestExtras['session_id'] ?? '',
            'cookie'                => $this->requestExtras['cookie'] ?? '',
            'token'                 => $this->requestExtras['token'] ?? '',
            'remote_request_uri'    => $this->requestExtras['remote_request_uri'] ?? '',
            'api_request_uuid'      => $this->requestExtras['api_request_uuid'] ?? '',
            'remote_request_uuid'   => $this->requestExtras['remote_request_uuid'] ?? '',
        ];

        return $record->with(extra: array_merge($record->extra, $extras));
    }
}
