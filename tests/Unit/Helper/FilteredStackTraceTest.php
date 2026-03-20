<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Helper;

use DvsaLogger\Helper\FilteredStackTrace;
use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionMethod;
use ReflectionObject;

class FilteredStackTraceTest extends TestCase
{
    /**
     * @dataProvider sensitiveArgumentNamesProvider
     * @throws ReflectionException
     */
    public function testItMasksSensitiveArguments(string $argName): void
    {
        $filter = $this->getMockBuilder(FilteredStackTrace::class)
            ->onlyMethods(['getArgumentNames'])
            ->getMock();

        $filter->method('getArgumentNames')->willReturn([$argName]);

        $traceLine = [
            'file' => 'file.php',
            'line' => 42,
            'function' => 'foo',
            'class' => null,
            'args' => ['mySecret'],
        ];

        $traceString = $this->invokeGetTraceLineAsFilteredString($filter, 0, $traceLine);

        $this->assertStringContainsString("'******'", $traceString);
        $this->assertStringNotContainsString('mySecret', $traceString);
    }

    public static function sensitiveArgumentNamesProvider(): array
    {
        return [
            ['password'],
            ['pwd'],
            ['pass'],
            ['newPassword'],
        ];
    }

    /**
     * @throws ReflectionException
     */
    public function testItDoesNotMaskNonSensitiveArguments(): void
    {
        $filter = $this->getMockBuilder(FilteredStackTrace::class)
            ->onlyMethods(['getArgumentNames'])
            ->getMock();

        $filter->method('getArgumentNames')->willReturn(['username']);

        $traceLine = [
            'file' => 'file.php',
            'line' => 42,
            'function' => 'foo',
            'class' => null,
            'args' => ['myUser'],
        ];

        $traceString = $this->invokeGetTraceLineAsFilteredString($filter, 0, $traceLine);

        $this->assertStringContainsString("username='myUser'", $traceString);
    }

    /**
     * @dataProvider argumentTypeProvider
     * @throws ReflectionException
     */
    public function testItHandlesVariousArgumentTypes($argValue, string $expected): void
    {
        $filter = $this->getMockBuilder(FilteredStackTrace::class)
            ->onlyMethods(['getArgumentNames'])
            ->getMock();

        $filter->method('getArgumentNames')->willReturn(['foo']);

        $traceLine = [
            'file' => 'file.php',
            'line' => 42,
            'function' => 'foo',
            'class' => null,
            'args' => [$argValue],
        ];

        $traceString = $this->invokeGetTraceLineAsFilteredString($filter, 0, $traceLine);

        $this->assertStringContainsString($expected, $traceString);
    }

    public static function argumentTypeProvider(): array
    {
        return [
            ['bar', "foo='bar'"],
            [null, "foo=NULL"],
            [true, "foo=true"],
            [false, "foo=false"],
            [123, "foo=123"],
            [[1,2,3], "foo=Array"],
            [new \stdClass(), "foo=Object(stdClass)"],
        ];
    }

    /**
     * @throws ReflectionException
     */
    public function testItHandlesReflectionFailure(): void
    {
        $filter = $this->getMockBuilder(FilteredStackTrace::class)
            ->onlyMethods(['getArgumentNames'])
            ->getMock();

        $filter->method('getArgumentNames')
            ->willThrowException(new ReflectionException());

        $traceLine = [
            'file' => 'file.php',
            'line' => 42,
            'function' => 'foo',
            'class' => null,
            'args' => ['bar'],
        ];

        $traceString = $this->invokeGetTraceLineAsFilteredString($filter, 0, $traceLine);

        $this->assertStringContainsString("'######'", $traceString);
    }

    /**
     * @throws ReflectionException
     */
    public function testItHandlesEmptyTrace(): void
    {
        $filter = new FilteredStackTrace();
        $exception = new Exception('empty');

        // Overwrite the trace to be empty
        $reflection = new ReflectionObject($exception);
        $property = $reflection->getProperty('trace');
        $property->setAccessible(true);
        $property->setValue($exception, []);

        $trace = $filter->getTraceAsString($exception);

        $this->assertSame('', $trace);
    }

    public function testItReturnsTraceAsString(): void
    {
        $filter = new FilteredStackTrace();
        $exception = new Exception('test');

        $trace = $filter->getTraceAsString($exception);

        $this->assertIsString($trace);
        $this->assertNotEmpty($trace);
    }

    /**
     * Helper to call protected method getTraceLineAsFilteredString
     * @throws ReflectionException
     */
    private function invokeGetTraceLineAsFilteredString(
        FilteredStackTrace $filter,
        int $count,
        array $line,
    ): string {
        $method = new ReflectionMethod($filter, 'getTraceLineAsFilteredString');
        $method->setAccessible(true);
        return $method->invoke($filter, $count, $line);
    }
}
