<?php

namespace DvsaLogger\Adapter;

use Doctrine\DBAL\Logging\SQLLogger;
use DvsaLogger\Service\DoctrineQueryLoggerService;

/**
 * DBAL 3 compatibility adapter.
 *
 * Not available when running DBAL 4.
 */
class DoctrineDbal3SqlLoggerAdapter implements SQLLogger
{
    public function __construct(
        private readonly DoctrineQueryLoggerService $doctrineQueryLoggerService
    ) {
    }

    public function startQuery(
        $sql,
        ?array $params = null,
        ?array $types = null
    ): void {
        $this->doctrineQueryLoggerService->startQuery(
            $sql,
            $params,
            $types
        );
    }

    public function stopQuery(): void
    {
        $this->doctrineQueryLoggerService->stopQuery();
    }
}
