<?php

declare(strict_types=1);

namespace DvsaLogger\Service;

use DvsaLogger\Debugger\BacktraceDebugger;
use DvsaLogger\Logger\MotLogger;

/**
 * Bridges Doctrine's SQL logging with the MOT logger.
 *
 * For DBAL 3, register as sql_logger.
 * For DBAL 4, the SQLLogger interface was removed - use this as a
 * standalone service and call startQuery/stopQuery directly.
 */
class DoctrineQueryLoggerService
{
    protected string $sql = '';
    /** @var list<mixed>|array<string, mixed>|null  */
    protected ?array $params = null;
    /** @var array<int|string, mixed>|null  */
    protected ?array $types = null;
    protected float $startTime = 0;

    public function __construct(
        private readonly MotLogger $logger,
        private readonly bool $enabled = false,
        private ?BacktraceDebugger $debugger = null
    ) {
        $this->debugger = $debugger ?? new BacktraceDebugger();
    }


    public function startQuery(string $sql, ?array $params = null, ?array $types = null): void
    {
        $this->sql = $sql;
        $this->params = $params;
        $this->types = $types;
        $this->startTime = microtime(true);
    }

    public function stopQuery(): void
    {
        if ($this->enabled) {
            $this->logger->debug('', [
                'query'      => $this->sql,
                'parameters' => json_encode($this->params),
                'types'      => $this->types,
                'query_time' => microtime(true) - $this->startTime,
                'context'    => json_encode($this->getContext()),
            ]);
        }
    }

    /**
     * @return array{repository_class?: string, repository_method?: string}
     */
    private function getContext(): array
    {
        if ($this->debugger === null) {
            return [];
        }
        $call = $this->debugger->findCall(
            'Repository',
            debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 15)
        );

        if ($call) {
            return [
                'repository_class' => $call->getClass(),
                'repository_method' => $call->getMethod(),
            ];
        }

        return [];
    }
}
