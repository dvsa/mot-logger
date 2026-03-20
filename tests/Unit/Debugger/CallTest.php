<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Debugger;

use DvsaLogger\Debugger\Call;
use PHPUnit\Framework\TestCase;

class CallTest extends TestCase
{
    public function testItExposesClassAndMethod(): void
    {
        $call = new Call('MyClass', 'myMethod');

        $this->assertSame('MyClass', $call->getClass());
        $this->assertSame('myMethod', $call->getMethod());
    }
}
