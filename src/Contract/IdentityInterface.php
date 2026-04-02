<?php

declare(strict_types=1);

namespace DvsaLogger\Contract;

interface IdentityInterface
{
    public function getUsername(): string;

    public function getUserId(): int;

    public function getUuid(): string;

    public function isPasswordChangeRequired(): bool;

    public function isAccountClaimRequired(): bool;
}
