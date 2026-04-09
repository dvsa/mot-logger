<?php

declare(strict_types=1);

namespace DvsaLogger\Processor;

use Monolog\LogRecord;

class DvsaMetadataProcessor
{
    public function __invoke(LogRecord $record): LogRecord
    {
        if (empty($record->context)) {
            return $record;
        }

        $context = $record->context;
        $extra = $record->extra;

        if (isset($context['__dvsa_metadata__'])) {
            $extra['__dvsa_metadata__'] = $context['__dvsa_metadata__'];
            unset($context['__dvsa_metadata__']);
        }

        foreach ($context as $key => $value) {
            $extra[$key] = $value;
        }

        return $record->with(extra: $extra, context: []);
    }
}
