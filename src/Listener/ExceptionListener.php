<?php

declare(strict_types=1);

namespace DvsaLogger\Listener;

use DvsaLogger\Contract\TokenServiceInterface;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Throwable;

/**
 * Listens for Laminas MVC dispatch errors and logs exceptions at CRIT level.
 *
 * Unwraps nested ServiceNotCreatedException chains and injects request context
 * (token, trace ID, API request UUID) into the log entry.
 */
class ExceptionListener
{
    private array $listeners = [];

    public function __construct(
        private readonly object $logger,
        private readonly ?TokenServiceInterface $tokenService = null
    ) {
    }

    public function attach(EventManagerInterface $events, int $priority = 1): void
    {
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_DISPATCH_ERROR,
            [$this, 'processException'],
            PHP_INT_MAX
        );
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_RENDER_ERROR,
            [$this, 'processException'],
            PHP_INT_MAX
        );
    }

    public function detach(EventManagerInterface $events): void
    {
        foreach ($this->listeners as $listener) {
            $events->detach($listener);
        }
        $this->listeners = [];
    }

    public function processException(MvcEvent $event): void
    {
        $exception =  $event->getParam('exception');
        if ($exception === null) {
            return;
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        if (
            is_a($exception, 'DvsaCommon\\Exception\\UnauthorisedException') ||
            is_a($exception, 'DvsaCommon\\HttpRestJson\\Exception\\ForbiddenOrUnauthorisedException')
        ) {
            return;
        }

        while ($exception instanceof ServiceNotCreatedException && $exception->getPrevious() !== null) {
            $exception = $exception->getPrevious();
        }

        $this->injectLoggerContext();

        /** @var Throwable $exception */
        $this->logger->crit($exception->getMessage(), ['ex' => $exception]);
    }

    private function injectLoggerContext(): void
    {
        if (method_exists($this->logger, 'setTraceId')) {
            $traceId = $this->getEnv('TRACE_ID');
            if ($traceId !== '') {
                $this->logger->setTraceId($traceId);
            }

            $spanId = $this->getEnv('SPAN_ID');
            if ($spanId !== '') {
                $this->logger->setSpanId($spanId);
            }

            $parentSpanId = $this->getEnv('PARENT_SPAN_ID');
            if ($parentSpanId !== '') {
                $this->logger->setParentSpanId($parentSpanId);
            }
        }

        if (method_exists($this->logger, 'setToken')) {
            try {
                $token = $this->tokenService?->getToken();
                if (is_string($token) && $token !== '') {
                    $this->logger->setToken($token);
                }
            } catch (Throwable $exception) {
                error_log(sprintf(
                    'Error retrieving token from TokenService: %s',
                    $exception->getMessage(),
                ));
            }
        }
    }

    protected function getEnv(string $name): string
    {
        return (string) ($_ENV[$name] ?? getenv($name)) ?: '';
    }
}
