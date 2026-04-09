<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Contract;

use DvsaLogger\Contract\TokenServiceInterface;
use PHPUnit\Framework\TestCase;

class TokenServiceInterfaceTest extends TestCase
{
    public function testGetTokenReturnsToken(): void
    {
        $service = $this->createTokenService('my-secret-token');
        $this->assertSame('my-secret-token', $service->getToken());
    }

    public function testGetTokenReturnsNull(): void
    {
        $service = $this->createTokenService(null);
        $this->assertNull($service->getToken());
    }

    private function createTokenService(?string $token): TokenServiceInterface
    {
        return new class ($token) implements TokenServiceInterface {
            public function __construct(private readonly ?string $token)
            {
            }

            public function getToken(): null|string
            {
                return $this->token;
            }
        };
    }
}
