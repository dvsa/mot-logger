<?php

namespace DvsaLogger\Service;

use DvsaLogger\Debugger\BacktraceDebugger;
use Laminas\Log\LoggerAwareInterface;
use Laminas\Log\LoggerAwareTrait;
use Laminas\Log\LoggerInterface;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Logging\SQLLogger;

/**
 * Class DoctrineLoggerService
 *
 * @package DvsaDoctrineLogger\Service
 *
 * @psalm-suppress DeprecatedInterface
 */
class DoctrineQueryLoggerService implements SQLLogger, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var bool */
    protected $enabled;

    /** @var string */
    protected $sql;
    /** @var array|null */
    protected $params;
    /** @var array|null */
    protected $types;
    /** @var float */
    protected $startTime;
    /** @var BacktraceDebugger */
    protected $debugger;

    /**
     * @param LoggerInterface $logger
     * @param bool $enabled
     */
    public function __construct($logger, $enabled = false, BacktraceDebugger $debugger = null)
    {
        $this->sql = '';
        $this->startTime = 0;
        $this->logger = $logger;
        $this->enabled = $enabled;
        $this->debugger = $debugger ?: new BacktraceDebugger();
    }

    /**
     * Logs a SQL statement somewhere.
     *
     * @param string                                                                    $sql    SQL statement
     * @param list<mixed>|array<string, mixed>|null                                     $params Statement parameters
     * @param array<int, Type|int|string|null>|array<string, Type|int|string|null>|null $types  Parameter types
     *
     * @return void
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        $this->sql = $sql;
        $this->params = $params;
        $this->types = $types;
        $this->startTime = microtime(true);
    }

    /**
     * Marks the last started query as stopped. This can be used for timing of queries.
     *
     * @return void
     */
    public function stopQuery()
    {
        if ($this->enabled) {
            $this->logger->debug(
                '',
                [
                    'query'      => $this->sql,
                    'parameters' => json_encode($this->params),
                    'types'      => $this->types,
                    'query_time' => microtime(true) - $this->startTime,
                    'context'    => json_encode($this->getContext()),
                ]
            );
        }
    }

    /**
     * @return array
     */
    private function getContext()
    {
        if ($call = $this->debugger->findCall('Repository', debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 15))) {
            return ['repository_class' => $call->getClass(), 'repository_method' => $call->getMethod()];
        }

        return [];
    }
}
