<?php

    declare(strict_types=1);

    namespace DvsaLogger\Util;

use DvsaLogger\Logger\SystemLogger;
use Monolog\Level;

trait LoggerSpyTrait
{
    private function createLoggerSpy(?string $uuid = ''): object
    {
        return new class ($uuid) {
            public bool $debugCalled = false;
            public array $capturedContext = [];
            public string $requestUuid = '';
            public bool $closedCalled = false;

            public function __construct(string $uuid)
            {
                $this->requestUuid = $uuid;
            }

            public function debug(string $msg, array $context = []): object
            {
                $this->debugCalled = true;
                $this->capturedContext = $context;
                return $this;
            }

            public function getRequestUuid(): string
            {
                return $this->requestUuid;
            }

            public function closeHandlers(): void
            {
                $this->closedCalled = true;
            }

            public function getBasicMetadata(Level $level): array
            {
                return [
                    'username' => 'test-user',
                    'level' => $level
                ];
            }
        };
    }

    private function createSystemLoggerSpy(array $replaceMap = []): SystemLogger
    {
        return new class ($replaceMap) extends SystemLogger {
            /** @var list<array<string, string>>  */
            public array $written = [];

            /** @param array<string, string> $replaceMap */
            public function __construct(array $replaceMap = [])
            {
                parent::__construct($replaceMap);
            }

            protected function writeToErrorLog(string $message, string $stackTrace): void
            {
                $this->written[] = ['message' => $message, 'stacktrace' => $stackTrace];
            }
        };
    }
}
