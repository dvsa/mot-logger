<?php

declare(strict_types=1);

namespace DvsaLogger\Processor;

use DvsaLogger\Listener\RequestListener;
use Monolog\LogRecord;

/**
 * Enriches log records for frontend request logging.
 * This processor is deprecated and will be removed in a future release.
 *
 * @deprecated Use DvsaLogger\Listener\RequestListener instead, which embeds context inline.
 * @see RequestListener
 */
class ExtrasProcessor
{
    private const URI_MAX_LENGTH        = 255;
    private const USER_AGENT_MAX_LENGTH = 255;

    public function __construct(
        private readonly array $requestExtras = [],
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {

        $extras = [
            'uri'               => substr($this->requestExtras['uri'] ?? '', 0, self::URI_MAX_LENGTH),
            'ip_address'        => $this->requestExtras['ip_address'] ?? '',
            'user_agent'        => substr($this->requestExtras['user_agent'] ?? '', 0, self::USER_AGENT_MAX_LENGTH),
            'php_session_id'    => $this->requestExtras['php_session_id'] ?? '',
            'route'             => $this->requestExtras['route'] ?? '',
            'request_uuid'      => $this->requestExtras['request_uuid'] ?? '',
            'token'             => $this->requestExtras['token'] ?? '',
            'memory_usage'      => $this->requestExtras['memory_usage'] ?? 0,
        ];

        return $record->with(extra: array_merge($record->extra, $extras));
    }
}
