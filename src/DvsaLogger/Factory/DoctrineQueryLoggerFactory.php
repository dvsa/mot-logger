<?php

namespace DvsaLogger\Factory;

use Interop\Container\ContainerInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Log\Formatter\Db as DbFormatter;
use Laminas\Log\Logger;
use Laminas\Log\Writer\Db as DbWriter;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Log\Processor\ProcessorInterface;

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
        $writer = new DbWriter($adapter, $this->getTableName($config), $this->getColumnMap($config));
        $writer->setFormatter(new DbFormatter('Y:m:d H:i:s'));
        $this->logger->addWriter($writer);

        /** @var ProcessorInterface | string */
        $processor = $container->get('DvsaLogger\DoctrineQueryExtras');
        $this->logger->addProcessor($processor);

        return $this->logger;
    }

    /**
     * @return string | null
     */
    private function getTableName(array $config)
    {
        /** @var string | null */
        return $this->getFromOptions($config, 'tableName');
    }

    /**
     * @return array | null
     */
    private function getColumnMap(array $config)
    {
        /** @var array | null */
        return $this->getFromOptions($config, 'columnMap');
    }

    /**
     * @return mixed
     */
    private function getFromOptions(array $config, string $propName)
    {
        if (
            array_key_exists('listeners', $config) &&
            is_array($config['listeners']) &&
            array_key_exists('api_client_request', $config['listeners']) &&
            is_array($config['listeners']['api_client_request']) &&
            array_key_exists('options', $config['listeners']['api_client_request']) &&
            is_array($config['listeners']['api_client_request']['options']) &&
            array_key_exists($propName, $config['listeners']['api_client_request']['options'])
        ) {
            /** @var mixed */
            return $config['listeners']['api_client_request']['options'][$propName];
        }

        return null;
    }
}
