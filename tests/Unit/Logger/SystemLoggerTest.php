<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Logger;

use DvsaLogger\Logger\SystemLogger;
use DvsaLogger\Util\LoggerSpyTrait;
use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

class SystemLoggerTest extends TestCase
{
    use LoggerSpyTrait;

    public function testItLogsExceptionToErrorLog(): void
    {
        $logger = $this->createSystemLoggerSpy();

        $exception = new Exception('test error', 500);
        $logger->recursiveLogExceptionToSystemLog($exception);

        $this->assertCount(1, $logger->written);
        $this->assertSame('test error', $logger->written[0]['message']);
        $this->assertNotEmpty($logger->written[0]['stacktrace']);
    }

    public function testItWalksExceptionChain(): void
    {
        $logger = $this->createSystemLoggerSpy();

        $inner = new RuntimeException('inner error');
        $outer = new RuntimeException('outer error', 0, $inner);

        $logger->recursiveLogExceptionToSystemLog($outer);

        $this->assertCount(2, $logger->written);
        $this->assertSame('outer error', $logger->written[0]['message']);
        $this->assertSame('inner error', $logger->written[1]['message']);
    }

    public function testItMasksSensitiveValuesInStackTrace(): void
    {
        $secretValue = 'secretValue';
        $mask = '**MASKED**';
        $logger = $this->createSystemLoggerSpy([$secretValue => $mask]);

        try {
            $this->throwWithSecretValue($secretValue);
        } catch (Exception $exception) {
            $logger->recursiveLogExceptionToSystemLog($exception);
        }

        $this->assertCount(1, $logger->written);
        $this->assertStringContainsString($mask, $logger->written[0]['stacktrace']);
        $this->assertStringNotContainsString($secretValue, $logger->written[0]['stacktrace']);
    }

    public function testEmptyReplaceMapDoesNotMask(): void
    {
        $logger = $this->createSystemLoggerSpy();

        try {
            $this->throwWithSecretValue('super-secret-value');
        } catch (Exception $exception) {
            $logger->recursiveLogExceptionToSystemLog($exception);
        }

        $this->assertCount(1, $logger->written);
        $this->assertSame('super-secret-value', $logger->written[0]['message']);
    }

    /**
     * @throws ReflectionException
     */
    public function testWriteToErrorLogWritesExpectedMessage(): void
    {
        $logger = new SystemLogger();
        $ref = new ReflectionClass($logger);
        $method = $ref->getMethod('writeToErrorLog');
        $method->setAccessible(true);

        $tmpFile = tempnam(sys_get_temp_dir(), 'errlog');
        $originalErrorLog = ini_set('error_log', $tmpFile);

        $message = 'Test error message';
        $stackTrace = 'Test stack trace';
        $expected = $message . ' StackTrace: ' . $stackTrace;

        $method->invoke($logger, $message, $stackTrace);
        clearstatcache();
        $logContents = file_get_contents($tmpFile);

        $this->assertStringContainsString($expected, $logContents);

        if ($originalErrorLog !== false) {
            ini_set('error_log', $originalErrorLog);
        }
        unlink($tmpFile);
    }

    /**
     * @throws Exception
     */
    private function throwWithSecretValue($secretValue)
    {
        throw new Exception($secretValue);
    }
}
