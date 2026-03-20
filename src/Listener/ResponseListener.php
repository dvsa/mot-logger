<?php

declare(strict_types=1);

namespace DvsaLogger\Listener;

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
        $request = $event->getRequest();
        if (!$request instanceof Request) {
            return;
        }

        $response = $event->getResponse();
        if (!($response instanceof Response)) {
            return;
        }

        $headers = $response->getHeaders();

        $contentType = '';
        $ctHeader = $headers->get('Content-Type');
        if ($ctHeader instanceof ContentType) {
            $contentType = $ctHeader->getFieldValue();
        }

        $requestStartTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
        $executionTime = microtime(true) - $requestStartTime;

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

        $requestUuid = '';
        if (method_exists($this->logger, 'getRequestUuid')) {
            try {
                $requestUuid = $this->logger->getRequestUuid();
            } catch (Throwable) {
            }
        }

        $this->logger->debug('', [
            'status_code'               => $response->getStatusCode(),
            'content_type'              => $contentType,
            'response_content'          => substr($response->getContent(), 0, 1000),
            'execution_time'            => $executionTime,
            'token'                     => $token,
            'api_request_uuid'          => $requestUuid,
            'frontend_request_uuid'     => $frontendRequestUuid,
        ]);
    }

    public function shutdown(): void
    {
        if (method_exists($this->logger, 'closeHandlers')) {
            $this->logger->closeHandlers();
        }
    }
}
