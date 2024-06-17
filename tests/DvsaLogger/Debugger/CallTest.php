<?php

namespace DvsaLogger\Debugger;

use PHPUnit\Framework\TestCase;

class CallTest extends TestCase
{
    /**
     * @return void
     */
    public function testItExposesClassAndMethod()
    {
        $call = new Call('MyClass', 'myMethod');

        $this->assertSame('MyClass', $call->getClass());
        $this->assertSame('myMethod', $call->getMethod());
    }
}
