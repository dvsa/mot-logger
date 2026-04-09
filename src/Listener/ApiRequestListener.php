<?php

declare(strict_types=1);

namespace DvsaLogger\Listener;

use DvsaLogger\Helper\ResolvePhpRequestTrait;
use DvsaLogger\Helper\UuidGeneratorTrait;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Header\Authorization;
use Laminas\Http\Header\GenericHeader;
use Laminas\Http\Header\UserAgent;
use Laminas\Http\PhpEnvironment\RemoteAddress;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Mvc\MvcEvent;
use Random\RandomException;
use Throwable;

/**
 * Listens for Laminas MVC route events for API request logging.
 * Attaches to EVENT_ROUTE and logs with API-specific processor context.
 */
class ApiRequestListener
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

        $this->logger->debug('', $this->buildLogContext($request));
    }

    /**
     * @throws RandomException
     */
    private function buildLogContext(Request $request): array
    {
        return [
            'api_request_uuid'      => $this->getApiRequestUuid(),
            'uri'                   => $request->getUriString(),
            'request_method'        => $request->getMethod(),
            'parameters'            => [
                'get_vars'  => $request->getQuery()->toArray(),
                'post_vars' => $request->getContent(),
            ],
            'token'                 => $this->getAuthToken($request),
            'frontend_request_uuid' => $this->getFrontendRequestUuid($request),
            'ip_address'            => $this->getIpAddress($request),
            'user_agent'            => $this->getUserAgent($request),
        ];
    }

    /**
     * @throws RandomException
     */
    private function getApiRequestUuid(): string
    {
        if (!method_exists($this->logger, 'getRequestUuid')) {
             return $this->generateUuid();
        }

        try {
            $uuid = $this->logger->getRequestUuid();
            return $uuid !== '' ? $uuid : $this->generateUuid();
        } catch (Throwable $exception) {
            error_log(sprintf(
                'Error retrieving API request UUID from logger: %s',
                $exception->getMessage(),
            ));
            return $this->generateUuid();
        }
    }

    private function getAuthToken(Request $request): string
    {
        $authHeader = $request->getHeader('Authorization');
        return $authHeader instanceof Authorization
            ? $authHeader->getFieldValue()
            : '';
    }

    private function getFrontendRequestUuid(Request $request): string
    {
        $uuidHeader = $request->getHeader('X-request-uuid');
        return $uuidHeader instanceof GenericHeader
            ? $uuidHeader->getFieldValue()
            : '';
    }

    private function getIpAddress(Request $request): string
    {
        return (new RemoteAddress())->getIpAddress();
    }

    private function getUserAgent(Request $request): string
    {
        $userAgent = $request->getHeader('User-Agent');
        return $userAgent instanceof UserAgent
            ? $userAgent->getFieldValue()
            : '';
    }
}
