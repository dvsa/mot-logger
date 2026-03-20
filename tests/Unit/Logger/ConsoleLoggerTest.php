<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Logger;

use DvsaLogger\Formatter\PipeDelimitedFormatter;
use DvsaLogger\Logger\ConsoleLogger;
use DvsaLogger\Logger\MotLogger;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Random\RandomException;
use DvsaLogger\Contract\IdentityProviderInterface;
use DvsaLogger\Contract\TokenServiceInterface;
use ReflectionClass;

class ConsoleLoggerTest extends TestCase
{
    private Logger $monolog;
    private ConsoleLogger $consoleLogger;

    /**
     * @throws RandomException
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->monolog = new Logger('test');
        $this->consoleLogger = new ConsoleLogger($this->monolog);
    }

    public function testExtendsMotLogger(): void
    {
        $this->assertInstanceOf(MotLogger::class, $this->consoleLogger);
    }

    public function testHasStdoutHandler(): void
    {
        $handlers = $this->monolog->getHandlers();
        $this->assertCount(1, $handlers);
        $this->assertInstanceOf(StreamHandler::class, $handlers[0]);
    }

    public function testHandlerIsPushedToTopAndLevelIsDebug(): void
    {
        $handler = $this->getFirstHandler();
        $this->assertSame(Level::Debug, $handler->getLevel());
    }

    public function testHandlerUsesPipeDelimitedFormatter(): void
    {
        $formatter = $this->getFirstFormatter();
        $this->assertInstanceOf(PipeDelimitedFormatter::class, $formatter);
    }

    /**
     * @throws RandomException
     */
    public function testLogOutputIsPipeDelimited(): void
    {
        $stream = fopen('php://memory', 'rw');

        $handler = new StreamHandler($stream, Level::Debug);
        $handler->setFormatter(new PipeDelimitedFormatter());

        $monolog = new Logger('test');
        $logger = new ConsoleLogger($monolog);
        $monolog->setHandlers([$handler]); // Override any handlers added by ConsoleLogger
        $logger->info('Test message', ['foo' => 'bar']);

        rewind($stream);

        $output = stream_get_contents($stream);

        $this->assertStringContainsString('|', $output);
    }

    public function testGetBasicMetadataOverridesUsername(): void
    {
            $level = Level::Info;

            $reflection = new ReflectionClass($this->consoleLogger);

            $method = $reflection->getMethod('getBasicMetadata');
            $method->setAccessible(true);
            $metadata = $method->invoke($this->consoleLogger, $level);

            $this->assertArrayHasKey('username', $metadata);
            $this->assertSame('', $metadata['username']);
    }

    /**
     * @throws RandomException
     */
    public function testInstantiationWithIdentityProviderAndTokenService(): void
    {
        $monolog = new Logger('test');

        $identityProvider = $this->createMock(IdentityProviderInterface::class);
        $tokenService = $this->createMock(TokenServiceInterface::class);

        $logger = new ConsoleLogger($monolog, $identityProvider, $tokenService, 'uuid-123');

        $stream = fopen('php://memory', 'rw');

        $handler = new StreamHandler($stream, Level::Debug);
        $handler->setFormatter(new PipeDelimitedFormatter());
        $monolog->setHandlers([$handler]);

        $logger->info('Test', ['foo' => 'bar']);

        rewind($stream);

        $output = stream_get_contents($stream);

        $this->assertStringContainsString('|', $output);
    }

    private function getFirstHandler(): HandlerInterface
    {
        return $this->monolog->getHandlers()[0];
    }

    private function getFirstFormatter(): FormatterInterface
    {
        return $this->getFirstHandler()->getFormatter();
    }
}
