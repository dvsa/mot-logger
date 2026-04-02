<?php

declare(strict_types=1);

namespace DvsaLogger\Listener;

use DvsaLogger\Helper\ResolvePhpRequestTrait;
use DvsaLogger\Helper\UuidGeneratorTrait;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Header\UserAgent;
use Laminas\Http\PhpEnvironment\RemoteAddress;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Mvc\MvcEvent;
use Monolog\Level;
use Random\RandomException;
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
    use ResolvePhpRequestTrait;
    use UuidGeneratorTrait;

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
        $request = $this->resolvePhpEnvironmentRequest($event->getRequest());
        if ($request === null) {
            return;
        }

        $this->logger->debug('', $this->buildLogContext($request, $event));
    }

    /**
     * @throws RandomException
     */
    private function buildLogContext(Request $request, MvcEvent $event): array
    {
        return [
            'request_uuid'              => $this->getRequestUuid(),
            'uri'                       => $request->getUriString(),
            'request_method'            => $request->getMethod(),
            'ip_address'                => $this->getIpAddress(),
            'php_session_id'            => session_id(),
            'username'                  => $this->getUsername(),
            'route'                     => $event->getRouteMatch()?->getMatchedRouteName() ?? '',
            'parameters'                => [
                'get_vars'      => $request->getQuery()->toArray(),
                'post_vars'     => $request->getContent(),
                'route'         => $event->getRouteMatch()?->getParams() ?? [],
            ],
            'user_agent'                => $this->getUserAgent($request),
            'memory_usage'              => memory_get_usage(true),
        ];
    }

    /**
     * @throws RandomException
     */
    private function getRequestUuid(): string
    {
        if (method_exists($this->logger, 'getRequestUuid')) {
            try {
                return $this->logger->getRequestUuid();
            } catch (Throwable $exception) {
                error_log(sprintf(
                    'Error retrieving request UUID from logger: %s',
                    $exception->getMessage(),
                ));
            }
        }
        return $this->generateUuid();
    }

    private function getIpAddress(): string
    {
        try {
            $remoteAddress = new RemoteAddress();
            return $remoteAddress->getIpAddress();
        } catch (Throwable $exception) {
            error_log(sprintf(
                'Error retrieving IP address: %s',
                $exception->getMessage(),
            ));
            return '';
        }
    }


    private function getUsername(): string
    {
        if (method_exists($this->logger, 'getBasicMetadata')) {
            try {
                $meta = $this->logger->getBasicMetadata(Level::Debug);
                return $meta['username'] ?? '';
            } catch (Throwable $exception) {
                error_log(sprintf(
                    'Error retrieving username from logger metadata: %s',
                    $exception->getMessage(),
                ));
            }
        }
        return '';
    }

    private function getUserAgent(Request $request): string
    {
        $header = $request->getHeader('User-Agent');
        if ($header instanceof UserAgent) {
            return $header->getFieldValue();
        }
        return '';
    }
}
