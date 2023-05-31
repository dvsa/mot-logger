<?php

namespace DvsaLogger\Factory;

use Interop\Container\ContainerInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Log\Formatter\Db as DbFormatter;
use Laminas\Log\Logger;
use Laminas\Log\Writer\Db as DbWriter;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Class DoctrineQueryLoggerFactory
 *
 * @package DvsaLogger\Factory
 */
class DoctrineQueryLoggerFactory implements FactoryInterface
{
    protected $logger;

    /**
     * @param ContainerInterface $container
     * @param array|null $options
     * @return Logger
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $config       = $container->get('Config');
        $config       = $config['DvsaLogger'];
        $this->logger = new Logger();

        $adapter   = new Adapter($config['dbConfig']);
        $tableName = $config['loggers']['doctrine_query']['options']['tableName'];
        $columnMap = $config['loggers']['doctrine_query']['options']['columnMap'];

        $writer = new DbWriter($adapter, $tableName, $columnMap);
        $writer->setFormatter(new DbFormatter('Y:m:d H:i:s'));
        $this->logger->addWriter($writer);

        $processor = $container->get('DvsaLogger\DoctrineQueryExtras');
        $this->logger->addProcessor($processor);

        return $this->logger;
    }
}
