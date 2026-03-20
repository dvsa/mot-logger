<?php

declare(strict_types=1);

namespace DvsaLogger\Listener;

use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Header\Authorization;
use Laminas\Http\Header\GenericHeader;
use Laminas\Http\Header\UserAgent;
use Laminas\Http\PhpEnvironment\RemoteAddress;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Mvc\MvcEvent;
use Random\RandomException;

/**
 * Listens for Laminas MVC route events for API request logging.
 * Attaches to EVENT_ROUTE and logs with API-specific processor context.
 */
class ApiRequestListener
{
    private array $listeners = [];

    public function __construct(private readonly object $logger)
    {
    }

    public function attach(EventManagerInterface $events, int $priority = 1): void
    {
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_ROUTE,
            [$this, 'logRequest'],
            $priority,
        );
    }

    public function detach(EventManagerInterface $events): void
    {
        foreach ($this->listeners as $listener) {
            $events->detach($listener);
        }
        $this->listeners = [];
    }

    /**
     * @throws RandomException
     */
    public function logRequest(MvcEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request instanceof Request) {
            return;
        }

        $remoteAddress = new RemoteAddress();

        $token = '';
        $authHeader = $request->getHeader('Authorization');
        if ($authHeader instanceof Authorization) {
            $token = $authHeader->getFieldValue();
        }

        $frontendRequestUuid = '';
        $uuidHeader = $request->getHeader('X-request-uuid');
        if ($uuidHeader instanceof GenericHeader) {
            $frontendRequestUuid = $uuidHeader->getFieldValue();
        }

        $apiRequestUuid = '';
        if (method_exists($this->logger, 'getRequestUuid')) {
            try {
                $apiRequestUuid = $this->logger->getRequestUuid();
            } catch (\Throwable) {
            }
        }

        if ($apiRequestUuid === '') {
            $apiRequestUuid = bin2hex(random_bytes(16));
        }

        $userAgent = '';
        $uaHeader = $request->getHeader('UserAgent');
        if ($uaHeader instanceof UserAgent) {
            $userAgent = $uaHeader->getFieldValue();
        }

        $this->logger->debug('', [
            'api_request_uuid'       => $apiRequestUuid,
            'uri'                    => $request->getUriString(),
            'request_method'         => $request->getMethod(),
            'parameters'             => [
                'get_vars'  => $request->getQuery()->toArray(),
                'post_vars'  => $request->getContent(),
            ],
            'token'                  => $token,
            'frontend_request_uuid'  => $frontendRequestUuid,
            'ip_address'             => $remoteAddress->getIpAddress(),
            'user_agent'             => $userAgent,
        ]);
    }
}
