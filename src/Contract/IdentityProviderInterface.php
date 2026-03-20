<?php

namespace DvsaLogger\Contract;

interface IdentityProviderInterface
{
    public function getIdentity(): ?IdentityInterface;
}
