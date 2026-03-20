<?php

declare(strict_types=1);

namespace DvsaLogger\Listener;

use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Header\UserAgent;
use Laminas\Http\PhpEnvironment\RemoteAddress;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Mvc\MvcEvent;
use Monolog\Level;
use Throwable;

/**
 * Listens for Laminas MVC route events and logs the request at DEBUG level.
 *
 * BC note: the original FrontendRequest listener logged empty strings and relied
 * on an Extras processor (injected into a dedicated Laminas Logger) to enrich
 * every log call with HTTP context. Since this package uses a single shared
 * MotLogger, we inject the context directly at the call site.
 */
class RequestListener
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

    public function logRequest(MvcEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request instanceof Request) {
            return;
        }

        $routeMatch = $event->getRouteMatch();
        $remoteAddress =  new RemoteAddress();

        $username = '';
        if (method_exists($this->logger, 'getBasicMetadata')) {
            try {
                $meta = $this->logger->getBasicMetadata(Level::Debug);
                $username = $meta['username'] ?? '';
            } catch (Throwable) {
            }
        }

        $userAgent = '';
        $header = $request->getHeader('User-Agent');
        if ($header instanceof UserAgent) {
            $userAgent = $header->getFieldValue();
        }

        $route = $routeMatch ? $routeMatch->getMatchedRouteName() : '';
        $routeParams = $routeMatch ? $routeMatch->getParams() : [];
        $uri = $request->getUriString();

        $requestUuid = '';
        if (method_exists($this->logger, 'getRequestUuid')) {
            try {
                $requestUuid = $this->logger->getRequestUuid();
            } catch (Throwable) {
            }
        }

        $this->logger->debug('', [
           'request_uuid'               => $requestUuid,
            'uri'                       => substr($uri, 0, 255),
            'request_method'            => $request->getMethod(),
            'ip_address'                => $remoteAddress->getIpAddress(),
            'php_session_id'            => session_id(),
            'username'                  => $username,
            'route'                     => $route,
            'parameters'                => [
                'get_vars'  => $request->getQuery()->toArray(),
                'post_vars' => $request->getContent(),
                'route'     => $routeParams,
            ],
            'user_agent'                => $userAgent,
            'memory_usage'              => memory_get_usage(true),
        ]);
    }
}
