<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Contract;

use DvsaLogger\Contract\IdentityInterface;
use DvsaLogger\Contract\IdentityProviderInterface;
use PHPUnit\Framework\TestCase;

class IdentityProviderInterfaceTest extends TestCase
{
    public function testGetIdentityReturnsIdentity(): void
    {
        $identity = $this->createMock(IdentityInterface::class);
        $identity->method('getUsername')->willReturn('testUser');

        $provider = $this->createProvider($identity);
        $result = $provider->getIdentity();

        $this->assertNotNull($result);
        $this->assertSame($identity, $result);
        $this->assertSame('testUser', $result->getUsername());
    }

    private function createProvider(?IdentityInterface $identity): IdentityProviderInterface
    {
        return new class ($identity) implements IdentityProviderInterface {
            private ?IdentityInterface $identity;

            public function __construct(?IdentityInterface $identity)
            {
                $this->identity = $identity;
            }

            public function getIdentity(): ?IdentityInterface
            {
                return $this->identity;
            }
        };
    }
}
