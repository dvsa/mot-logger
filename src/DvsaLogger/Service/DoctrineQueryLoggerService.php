<?php

namespace DvsaLogger\Service;

use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\ORM\EntityRepository;
use DvsaLogger\Debugger\BacktraceDebugger;
use Laminas\Log\LoggerAwareInterface;
use Laminas\Log\LoggerAwareTrait;

/**
 * Class DoctrineLoggerService
 *
 * @package DvsaDoctrineLogger\Service
 */
class DoctrineQueryLoggerService implements SQLLogger, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected $enabled;

    protected $sql;
    protected $params;
    protected $types;
    protected $startTime;
    protected $debugger;

    public function __construct($logger, $enabled = false, BacktraceDebugger $debugger = null)
    {
        $this->logger = $logger;
        $this->enabled = $enabled;
        $this->debugger = $debugger ?: new BacktraceDebugger();
    }

    /**
     * {@inheritdoc}
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        $this->sql = $sql;
        $this->params = $params;
        $this->types = $types;
        $this->startTime = microtime(1);
    }

    /**
     * {@inheritdoc}
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
                    'query_time' => microtime(1) - $this->startTime,
                    'context'    => json_encode($this->getContext()),
                ]
            );
        }
    }

    /**
     * @return []
     */
    private function getContext()
    {
        if ($call = $this->debugger->findCall('Repository', debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 15))) {
            return ['repository_class' => $call->getClass(), 'repository_method' => $call->getMethod()];
        }

        return [];
    }
}
