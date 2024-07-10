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
 * Class DoctrineQueryLoggerFactory
 *
 * @package DvsaLogger\Factory
 *
 * @psalm-suppress MissingConstructor
 */
class DoctrineQueryLoggerFactory implements FactoryInterface
{
    /**
     * @var  Logger
     */
    protected $logger;

    /**
     * @param ContainerInterface $container
     * @param string $name
     * @param array|null $options
     * @return Logger
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        /** @var array */
        $config       = $container->get('Config');
        /** @var array */
        $config       = $config['DvsaLogger'];
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
        $processor = $container->get('DvsaLogger\DoctrineQueryExtras');
        $this->logger->addProcessor($processor);

        return $this->logger;
    }
}
