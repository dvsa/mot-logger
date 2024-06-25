<?php

namespace DvsaLogger\Interfaces;

use DvsaLogger\Interfaces\IdentityInterface;

interface MotFrontendIdentityProviderInterface
{
    public function getIdentity(): IdentityInterface|null;
}
