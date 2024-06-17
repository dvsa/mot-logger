<?php

namespace DvsaLogger\Debugger;

use PHPUnit\Framework\TestCase;

class BacktraceDebuggerTest extends TestCase
{
    /**
     * @return void
     */
    public function testItExtractsCallFromBacktrace()
    {
        $debugger = new BacktraceDebugger();

        $call = $debugger->findCall(BacktraceDebuggerTest::class, debug_backtrace());

        $this->assertInstanceOf(Call::class, $call);
        $this->assertEquals(
            new Call(BacktraceDebuggerTest::class, 'testItExtractsCallFromBacktrace'),
            $call
        );
    }

    /**
     * @return void
     */
    public function testItReturnsNullIfCallCannotBeFound()
    {
        $debugger = new BacktraceDebugger();

        $call = $debugger->findCall('MyClass', debug_backtrace());

        $this->assertNull($call);
    }

    /**
     * @return void
     */
    public function testItMatchesClassesByPartialName()
    {
        $debugger = new BacktraceDebugger();

        $call = $debugger->findCall('BacktraceDebugger', debug_backtrace());

        $this->assertEquals(
            new Call(BacktraceDebuggerTest::class, 'testItMatchesClassesByPartialName'),
            $call
        );
    }

    /**
     * @return void
     */
    public function testItFindsMethodCallsForClassParents()
    {
        $debugger = new BacktraceDebugger();

        $call = $debugger->findCall('TestCase', debug_backtrace());

        $this->assertEquals(
            new Call(TestCase::class, 'runTest'),
            $call
        );
    }
}
