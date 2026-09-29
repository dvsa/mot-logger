<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Logging;

interface SQLLogger
{
    public function startQuery(
        $sql,
        ?array $params = null,
        ?array $types = null
    ): void;

    public function stopQuery(): void;
}