<?php

declare(strict_types=1);

namespace DvsaLogger\Listener;

use DvsaLogger\Helper\ResolvePhpRequestTrait;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Header\Authorization;
use Laminas\Http\Header\ContentType;
use Laminas\Http\Header\GenericHeader;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\MvcEvent;
use Throwable;

/**
 * Listeners for Laminas MVC finish events and logs the response at DEBUG level.
 */
class ResponseListener
{
    use ResolvePhpRequestTrait;

    private array $listeners = [];

    public function __construct(private readonly object $logger)
    {
    }

    public function attach(EventManagerInterface $events, int $priority = 1): void
    {
        $events->attach(
            MvcEvent::EVENT_FINISH,
            [$this, 'logResponse'],
            $priority
        );
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_FINISH,
            [$this, 'shutdown'],
            -1000
        );
    }

    public function detach(EventManagerInterface $events): void
    {
        foreach ($this->listeners as $listener) {
            $events->detach($listener);
        }
        $this->listeners = [];
    }

    public function logResponse(MvcEvent $event): void
    {
        $request = $this->resolvePhpEnvironmentRequest($event->getRequest());
        if ($request === null) {
            return;
        }

        $response = $event->getResponse();
        if (!($response instanceof Response)) {
            return;
        }

        $this->logger->debug('', $this->buildLogContext($request, $response));
    }

    public function shutdown(): void
    {
        if (method_exists($this->logger, 'closeHandlers')) {
            $this->logger->closeHandlers();
        }
    }

    private function buildLogContext(Request $request, Response $response): array
    {
        return [
            'status_code' => $response->getStatusCode(),
            'content_type' => $this->getContentType($response),
            'response_content' => substr($response->getContent(), 0, 1000),
            'execution_time' => $this->getExecutionTime(),
            'token' => $this->getAuthToken($request),
            'api_request_uuid' => $this->getApiRequestUuid(),
            'frontend_request_uuid' => $this->getFrontendRequestUuid($request),
        ];
    }

    private function getContentType(Response $response): string
    {
        $headers = $response->getHeaders();
        $ctHeader = $headers->get('Content-Type');
        if ($ctHeader instanceof ContentType) {
            return $ctHeader->getFieldValue();
        }
        return '';
    }

    private function getExecutionTime(): string
    {
        $requestStartTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
        $executionTime = microtime(true) - $requestStartTime;
        return (string) $executionTime;
    }

    private function getAuthToken(Request $request): string
    {
        $authHeader = $request->getHeader('Authorization');
        if ($authHeader instanceof Authorization) {
            return $authHeader->getFieldValue();
        }
        return '';
    }

    private function getApiRequestUuid(): string
    {
        if (method_exists($this->logger, 'getRequestUuid')) {
            try {
                return $this->logger->getRequestUuid();
            } catch (Throwable $exception) {
                error_log(sprintf(
                    'Error retrieving API request UUID from logger: %s',
                    $exception->getMessage(),
                ));
            }
        }
        return '';
    }

    private function getFrontendRequestUuid(Request $request): string
    {
        $uuidHeader = $request->getHeader('X-request-uuid');
        if ($uuidHeader instanceof GenericHeader) {
            return $uuidHeader->getFieldValue();
        }
        return '';
    }
}
