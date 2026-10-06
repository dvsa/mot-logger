<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Adapter;

use Doctrine\DBAL\Logging\SQLLogger;
use DvsaLogger\Adapter\DoctrineDbal3SqlLoggerAdapter;
use DvsaLogger\Service\DoctrineQueryLoggerService;
use PHPUnit\Framework\TestCase;

class DoctrineDbal3SqlLoggerAdapterTest extends TestCase
{
    public function testItDelegatesStartQueryAndStopQuery(): void
    {
        if (!interface_exists(SQLLogger::class, false)) {
            require_once __DIR__ . '/../../Stubs/Doctrine/DBAL/Logging/SQLLogger.php';
        }

        $service = $this->createMock(DoctrineQueryLoggerService::class);
        $adapter = new DoctrineDbal3SqlLoggerAdapter($service);
        $sql = 'SELECT' . ' * FROM users WHERE id = ?';

        $service->expects($this->once())
            ->method('startQuery')
            ->with(
                $sql,
                ['id' => 1],
                ['integer'],
            );

        $service->expects($this->once())
            ->method('stopQuery');

        $this->assertInstanceOf(SQLLogger::class, $adapter);

        $adapter->startQuery(
            $sql,
            ['id' => 1],
            ['integer'],
        );
        $adapter->stopQuery();
    }
}
