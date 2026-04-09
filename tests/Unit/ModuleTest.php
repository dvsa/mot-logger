<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit;

use DvsaLogger\Listener\ApiClientRequestListener;
use DvsaLogger\Listener\ApiRequestListener;
use DvsaLogger\Listener\ExceptionListener;
use DvsaLogger\Listener\RequestListener;
use DvsaLogger\Listener\ResponseListener;
use DvsaLogger\Logger\ConsoleLogger;
use DvsaLogger\Logger\MotLogger;
use DvsaLogger\Module;
use DvsaLogger\Service\DoctrineQueryLoggerService;
use PHPUnit\Framework\TestCase;

class ModuleTest extends TestCase
{
    public function testGetConfigReturnsArray(): void
    {
        $module = new Module();
        $config = $module->getConfig();

        $this->assertNotEmpty($config);
        $this->assertIsArray($config);
    }

    public function testConfigContainsMotLoggerSection(): void
    {
        $module = new Module();
        $config = $module->getConfig();

        $this->assertArrayHasKey('mot_logger', $config);
        $this->assertArrayHasKey('writers', $config['mot_logger']);
        $this->assertArrayHasKey('listeners', $config['mot_logger']);
        $this->assertArrayHasKey('doctrine_query', $config['mot_logger']);
        $this->assertArrayHasKey('mask_credentials', $config['mot_logger']);
    }

    public function testConfigContainsServiceManagerSection(): void
    {
        $module = new Module();
        $config = $module->getConfig();

        $this->assertArrayHasKey('service_manager', $config);
        $this->assertArrayHasKey('factories', $config['service_manager']);
        $this->assertArrayHasKey('aliases', $config['service_manager']);
    }

    public function testServiceFactoriesAreRegistered(): void
    {
        $module = new Module();
        $config = $module->getConfig();

        $serviceManager = $config['service_manager'];
        $factories = $serviceManager['factories'];

        $this->assertArrayHasKey(MotLogger::class, $factories);
        $this->assertArrayHasKey(ConsoleLogger::class, $factories);
        $this->assertArrayHasKey(RequestListener::class, $factories);
        $this->assertArrayHasKey(ResponseListener::class, $factories);
        $this->assertArrayHasKey(ExceptionListener::class, $factories);
        $this->assertArrayHasKey(ApiRequestListener::class, $factories);
        $this->assertArrayHasKey(ApiClientRequestListener::class, $factories);
        $this->assertArrayHasKey(DoctrineQueryLoggerService::class, $factories);
    }

    public function testMotLoggerWritersDisabledByDefault(): void
    {
        $module = new Module();
        $config = $module->getConfig();
        $motLogger = $config['mot_logger'];
        $writers = $motLogger['writers'];

        foreach ($writers as $writer) {
            $this->assertFalse($writer['enabled'] ?? false);
        }
    }

    public function testNoEmptyStringAliases(): void
    {
        $module = new Module();
        $config = $module->getConfig();
        $serviceManager = $config['service_manager'];
        $aliases = $serviceManager['aliases'];

        foreach ($aliases as $alias => $target) {
            $this->assertNotEmpty($alias, "Alias key should not be an empty string");
            $this->assertNotEmpty($target, "Alias target should not be an empty string");
        }
    }
}
