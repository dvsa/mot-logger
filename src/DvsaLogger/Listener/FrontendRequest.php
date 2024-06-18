<?php

namespace DvsaLogger\Listener;

use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Log\LoggerAwareInterface;
use Laminas\Log\LoggerAwareTrait;
use Laminas\Mvc\MvcEvent;

/**
 * Class Request
 *
 * @package Application\Event
 */
class FrontendRequest implements ListenerAggregateInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var array
     */
    protected $listeners = array();

    /**
     * @return array
     */
    public function getListeners()
    {
        return $this->listeners;
    }

    /**
     * @param callable $listeners
     *
     * @return $this
     */
    public function addListener(callable $listeners)
    {
        $this->listeners[] = $listeners;

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
        $this->addListener($events->attach(MvcEvent::EVENT_ROUTE, array($this, 'logRequest')));
    }

    /**
     * @param EventManagerInterface $events
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->getListeners() as $index => $listener) {
            // @BUG $events->detach returns null
            if ($events->detach($listener)) {
                $this->removeListener($index);
            }
        }
    }

    /**
     * @param MvcEvent $event
     *
     * @return void
     */
    public function logRequest(MvcEvent $event)
    {
        if ($event->getRequest() instanceof \Laminas\Http\PhpEnvironment\Request) {
            $this->logger->debug('');
        }
    }
}
