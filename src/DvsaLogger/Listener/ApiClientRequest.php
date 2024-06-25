<?php

namespace DvsaLogger\Listener;

use Laminas\EventManager\Event;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\Log\Logger as Log;
use Laminas\Log\LoggerAwareInterface;
use Laminas\Log\LoggerAwareTrait;

/**
 * Class ApiClientRequest
 *
 * @package DvsaLogger\Listener
 *
 * @psalm-suppress MissingConstructor
 */
class ApiClientRequest implements ListenerAggregateInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var array */
    protected $listeners = [];

    /** @var  Log $log */
    protected $log;

    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $sharedEvents = $events->getSharedManager();
        if ($sharedEvents !== null) {
            $this->listeners[] = $sharedEvents->attach(
                'DvsaCommon\HttpRestJson\Client',
                'startOfRequest',
                array($this, 'logStartOfRequest'),
                100
            );
        }
    }

    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            $events->detach($listener);
            unset($this->listeners[$index]);
        }
    }

    /**
     * @param Event<string, array> $e
     *
     * @return void
     */
    public function logStartOfRequest(Event $e)
    {
        $this->logger->debug(
            '',
            ['endpoint_uri'   => $e->getParam('resourcePath'),
                 'request_method' => $e->getParam(
                     'request_method'
                 ), 'parameters'  => $e->getParam('content')]
        );
    }
}
