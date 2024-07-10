<?php

namespace DvsaLogger\Factory;

use Interop\Container\ContainerInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Log\Formatter\Db as DbFormatter;
use Laminas\Log\Logger;
use Laminas\Log\Writer\Db as DbWriter;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Log\Processor\ProcessorInterface;
use DvsaLogger\Service\DatabaseConfigurationService;

/**
 * Class FrontendRequestLoggerFactory
 *
 * @package DvsaLogger\Factory
 *
 * @psalm-suppress MissingConstructor
 */
class FrontendRequestLoggerFactory implements FactoryInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @return \Laminas\Log\Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return Logger
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var array */
        $config = $container->get('Config');
        /** @var array */
        $config = $config['DvsaLogger'];
        $this->logger = new Logger();

        /** @var array */
        $dbConfig = $config['dbConfig'];
        $adapter   = new Adapter($dbConfig);
        $tableName = DatabaseConfigurationService::getTableName($config);
        $columnMap = DatabaseConfigurationService::getColumnMap($config);

        $writer = new DbWriter($adapter, $tableName, $columnMap);
        $writer->setFormatter(new DbFormatter('Y:m:d H:i:s'));
        $this->logger->addWriter($writer);

        /** @var ProcessorInterface | string */
        $processor = $container->get('DvsaLogger\ExtrasProcessor');
        $this->logger->addProcessor($processor);

        return $this->logger;
    }
}
