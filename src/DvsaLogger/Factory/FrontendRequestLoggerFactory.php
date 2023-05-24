<?php

namespace DvsaLogger\Factory;

use Interop\Container\ContainerInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Log\Formatter\Db as DbFormatter;
use Laminas\Log\Logger;
use Laminas\Log\Writer\Db as DbWriter;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Class FrontendRequestLoggerFactory
 * @package DvsaLogger\Factory
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
        $config       = $container->get('Config');
        $config       = $config['DvsaLogger'];
        $this->logger = new Logger();

        $adapter   = new Adapter($config['dbConfig']);
        $tableName = $config['listeners']['frontend_request']['options']['tableName'];
        $columnMap = $config['listeners']['frontend_request']['options']['columnMap'];

        $writer = new DbWriter($adapter, $tableName, $columnMap);
        $writer->setFormatter(new DbFormatter('Y:m:d H:i:s'));
        $this->logger->addWriter($writer);

        $processor = $container->get('DvsaLogger\ExtrasProcessor');
        $this->logger->addProcessor($processor);

        return $this->logger;
    }
}
