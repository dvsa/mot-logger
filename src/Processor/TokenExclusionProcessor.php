<?php

declare(strict_types=1);

namespace DvsaLogger\Processor;

use Monolog\LogRecord;

/**
 * Processor that excludes token from __dvsa_metadata__.
 * Use this for handlers that should not log authentication tokens.
 */
class TokenExclusionProcessor
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $context = $record->context;

        if (isset($context['__dvsa_metadata__']) && is_array($context['__dvsa_metadata__'])) {
            $metadata = $context['__dvsa_metadata__'];
            unset($metadata['token']);
            $context['__dvsa_metadata__'] = $metadata;
            return $record->with(context: $context);
        }

        return $record;
    }
}
