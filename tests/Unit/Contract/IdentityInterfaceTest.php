<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Contract;

use DvsaLogger\Contract\IdentityInterface;
use PHPUnit\Framework\TestCase;

class IdentityInterfaceTest extends TestCase
{
    public function testImplementationProvidesUsername(): void
    {
        $identity = $this->createIdentity('testUser', 123, 'uuid-123', false, false);
        $this->assertEquals('testUser', $identity->getUsername());
    }

    public function testImplementationProvidesUserId(): void
    {
        $identity = $this->createIdentity('testUser', 9102, 'uuid-123', false, false);
        $this->assertSame(9102, $identity->getUserId());
    }

    public function testImplementationProvidesUuid(): void
    {
        $identity = $this->createIdentity('testUser', 123, 'uuid-123', false, false);
        $this->assertEquals('uuid-123', $identity->getUuid());
    }

    public function testImplementationProvidesPasswordChangeRequired(): void
    {
        $identity = $this->createIdentity('testUser', 123, 'uuid-123', true, false);
        $this->assertTrue($identity->isPasswordChangeRequired());
    }

    public function testImplementationProvidesAccountClaimRequired(): void
    {
        $identity = $this->createIdentity('testUser', 123, 'uuid-123', false, true);
        $this->assertTrue($identity->isAccountClaimRequired());
    }

    private function createIdentity(
        string $username,
        int $userId,
        string $uuid,
        bool $passwordChangeRequired,
        bool $accountClaimRequired,
    ): IdentityInterface {
        return new readonly class (
            $username,
            $userId,
            $uuid,
            $passwordChangeRequired,
            $accountClaimRequired,
        ) implements IdentityInterface {
            public function __construct(
                private string $username,
                private int $userId,
                private string $uuid,
                private bool $passwordChangeRequired,
                private bool $accountClaimRequired
            ) {
            }

            public function getUsername(): string
            {
                return $this->username;
            }

            public function getUserId(): int
            {
                return $this->userId;
            }

            public function getUuid(): string
            {
                return $this->uuid;
            }

            public function isPasswordChangeRequired(): bool
            {
                return $this->passwordChangeRequired;
            }

            public function isAccountClaimRequired(): bool
            {
                return $this->accountClaimRequired;
            }
        };
    }
}
