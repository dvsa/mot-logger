<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Debugger;

use DvsaLogger\Debugger\BacktraceDebugger;
use PHPUnit\Framework\TestCase;

class BacktraceDebuggerTest extends TestCase
{
    public function testItExtractsCallFromBacktrace(): void
    {
        $debugger = new BacktraceDebugger();

        $result = $debugger->findCall(BacktraceDebuggerTest::class, debug_backtrace());

        $this->assertNotNull($result);
        $this->assertSame(BacktraceDebuggerTest::class, $result->getClass());
        $this->assertSame('testItExtractsCallFromBacktrace', $result->getMethod());
    }

    public function testItReturnsNullIfCallCannotBeFound(): void
    {
        $debugger = new BacktraceDebugger();

        $result = $debugger->findCall('NonExistingClass', debug_backtrace());

        $this->assertNull($result);
    }

    public function testItMatchesClassesByPartialName(): void
    {
        $debugger = new BacktraceDebugger();

        $result = $debugger->findCall('BacktraceDebugger', debug_backtrace());

        $this->assertNotNull($result);
        $this->assertSame(BacktraceDebuggerTest::class, $result->getClass());
    }

    public function testItFindsMethodCallsForClassParents(): void
    {
        $debugger = new BacktraceDebugger();

        $result = $debugger->findCall('TestCase', debug_backtrace());

        $this->assertNotNull($result);
        $this->assertSame('runTest', $result->getMethod());
    }
}
