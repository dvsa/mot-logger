<?php

declare(strict_types=1);

namespace DvsaLogger\Helper;

class SapiHelper
{
    public function requestIsConsole(): bool
    {
        return php_sapi_name() === 'cli';
    }
}
