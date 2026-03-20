<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Factory;

use DvsaLogger\Factory\SapiHelperFactory;
use DvsaLogger\Helper\SapiHelper;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use ReflectionObject;

class SapiHelperFactoryTest extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     */
    public function testCreate(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $factory = new SapiHelperFactory();
        $helper = $factory($container, SapiHelper::class);

        $this->assertInstanceOf(
            SapiHelper::class,
            $helper,
            'Factory should create an instance of SapiHelper',
        );
        $this->assertSapiHelperIsStateless($helper);
    }

    private function assertSapiHelperIsStateless($helper): void
    {
        $reflection = new ReflectionObject($helper);
        $this->assertSame(0, count($reflection->getProperties()), 'SapiHelper should have no properties');
    }
}
