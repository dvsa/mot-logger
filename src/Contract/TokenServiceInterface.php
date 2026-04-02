<?php

declare(strict_types=1);

namespace DvsaLogger\Contract;

interface TokenServiceInterface
{
    public function getToken(): ?string;
}
