<?php

declare(strict_types=1);

namespace DvsaLogger\Contract;

class NoopTokenService implements TokenServiceInterface
{
    public function getToken(): ?string
    {
        return null;
    }
}
