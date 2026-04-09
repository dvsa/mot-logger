<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Contract;

use DvsaLogger\Contract\IdentityInterface;
use PHPUnit\Framework\TestCase;

class IdentityInterfaceTest extends TestCase
{
    public function testImplementationProvidesUsername(): void
    {
        $identity = $this->createIdentity(username: 'new-username');
        $this->assertEquals('new-username', $identity->getUsername());
    }

    public function testImplementationProvidesUserId(): void
    {
        $identity = $this->createIdentity(userId: 9102);
        $this->assertSame(9102, $identity->getUserId());
    }

    public function testImplementationProvidesUuid(): void
    {
        $identity = $this->createIdentity(uuid: 'test-uuid');
        $this->assertEquals('test-uuid', $identity->getUuid());
    }

    public function testImplementationProvidesPasswordChangeRequired(): void
    {
        $identity = $this->createIdentity(passwordChangeRequired: true);
        $this->assertTrue($identity->isPasswordChangeRequired());
    }

    public function testImplementationProvidesAccountClaimRequired(): void
    {
        $identity = $this->createIdentity(accountClaimRequired: true);
        $this->assertTrue($identity->isAccountClaimRequired());
    }

    private function createIdentity(
        string $username = 'testUser',
        int $userId = 123,
        string $uuid = 'uuid-123',
        bool $passwordChangeRequired = false,
        bool $accountClaimRequired = false,
    ): IdentityInterface {
        return new class (
            $username,
            $userId,
            $uuid,
            $passwordChangeRequired,
            $accountClaimRequired,
        ) implements IdentityInterface {
            public function __construct(
                private readonly string $username,
                private readonly int $userId,
                private readonly string $uuid,
                private readonly bool $passwordChangeRequired,
                private readonly bool $accountClaimRequired
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
