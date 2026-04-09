<?php

declare(strict_types=1);

namespace DvsaLogger\Helper;

use Random\RandomException;

trait UuidGeneratorTrait
{
    /**
     * @throws RandomException
     */
    private function generateUuid(): string
    {
        return bin2hex(random_bytes(16));
    }
}
