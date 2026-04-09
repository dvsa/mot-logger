<?php

declare(strict_types=1);

namespace DvsaLogger\Contract;

interface IdentityProviderInterface
{
    public function getIdentity(): ?IdentityInterface;
}
