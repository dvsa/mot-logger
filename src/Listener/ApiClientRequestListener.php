<?php

declare(strict_types=1);

namespace DvsaLogger\Listener;

use Laminas\EventManager\Event;
use Laminas\EventManager\EventManager;
use Throwable;

/**
 * Listens for outbound API client requests via Laminas shared events.
 */
class ApiClientRequestListener
{
    private object $logger;
    private array $listeners = [];

    public function __construct(object $logger)
    {
        $this->logger = $logger;
    }

    public function attach(EventManager $events, int $priority = 100): void
    {
        $sharedManager = $events->getSharedManager();
        if ($sharedManager !== null) {
            /** @psalm-suppress UndefinedFunction */
            $this->listeners[] = $sharedManager->attach(
                'DvsaCommon\\HttpRestJson\\Client',
                'startOfRequest',
                [$this, 'logStartOfRequest']
            );
        }
    }

    public function detach(EventManager $events): void
    {
        foreach ($this->listeners as $listener) {
            $events->detach($listener);
        }
        $this->listeners = [];
    }


    public function logStartOfRequest(Event $event): void
    {
        $requestUuid = '';
        if (method_exists($this->logger, 'getRequestUuid')) {
            try {
                $requestUuid = $this->logger->getRequestUuid();
            } catch (Throwable $exception) {
                error_log(sprintf(
                    'Error retrieving request UUID from logger: %s',
                    $exception->getMessage(),
                ));
            }
        }

        $this->logger->debug('', [
            'endpoint_uri'          => $event->getParam('resourcePath', ''),
            'request_method'        => $event->getParam('request_method', ''),
            'parameters'            => $event->getParam('content', ''),
            'request_uuid'          => $requestUuid,
        ]);
    }
}
