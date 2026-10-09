<?php

declare(strict_types=1);

namespace DvsaLogger\Contract;

class NoopIdentityProvider implements IdentityProviderInterface
{
    public function getIdentity(): ?IdentityInterface
    {
        return null;
    }
}
