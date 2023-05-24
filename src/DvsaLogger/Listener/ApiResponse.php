<?php

namespace DvsaLogger\Listener;

use Laminas\EventManager\EventInterface;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\Log\Logger as Log;
use Laminas\Log\LoggerAwareInterface;
use Laminas\Log\LoggerAwareTrait;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\CallbackHandler;

/**
 * Class ApiResponse
 *
 * @package DvsaLogger\Listener
 */
class ApiResponse implements ListenerAggregateInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var array
     */
    protected $ignoreMediaTypes = array();

    /**
     * @var array
     */
    protected $listeners = array();

    /**
     * @var Log
     */
    protected $log;

    /**
     * @param Log $log
     */
    public function __construct(Log $log = null)
    {
        if (!is_null($log)) {
            $this->setLog($log);
        }
    }

    /**
     * @return Log
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * @param Log $log
     *
     * @return $this
     */
    public function setLog(Log $log)
    {
        $this->log = $log;

        return $this;
    }

    /**
     * @param array $ignoreMediaTypes
     *
     * @return $this
     */
    public function setIgnoreMediaTypes(array $ignoreMediaTypes)
    {
        $this->ignoreMediaTypes = $ignoreMediaTypes;

        return $this;
    }

    /**
     * @return array
     */
    public function getIgnoreMediaTypes()
    {
        return $this->ignoreMediaTypes;
    }

    /**
     * @return array
     */
    public function getListeners()
    {
        return $this->listeners;
    }

    /**
     * @param callable $listener
     * @return $this
     */
    public function addListener(callable $listener)
    {
        $this->listeners[] = $listener;

        return $this;
    }

    /**
     * @param int $index
     *
     * @return bool
     */
    public function removeListener($index)
    {
        if (!empty($this->listeners[$index])) {
            unset($this->listeners[$index]);

            return true;
        }

        return false;
    }

    /**
     * @param EventManagerInterface $events
     * @param int $priority
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->addListener($events->attach(MvcEvent::EVENT_FINISH, array($this, 'logResponse')));
        $this->addListener($events->attach(MvcEvent::EVENT_FINISH, array($this, 'shutdown'), -1000));
    }

    /**
     * @param EventManagerInterface $events
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->getListeners() as $index => $listener) {
            if ($events->detach($listener)) {
                $this->removeListener($index);
            }
        }
    }

    /**
     * @param MvcEvent $event
     */
    public function logResponse(MvcEvent $event)
    {
        if ($event->getRequest() instanceOf \Laminas\Http\PhpEnvironment\Request) {
            $this->logger->debug('');
        }
    }

    /**
     * @param EventInterface $event
     */
    public function shutdown(EventInterface $event)
    {
        foreach ($this->log->getWriters() as $writer) {
            $writer->shutdown();
        }
    }
}
