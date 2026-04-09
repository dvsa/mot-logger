<?php

declare(strict_types=1);

namespace DvsaLogger\Logger;

use DateInvalidTimeZoneException;
use DateTimeImmutable;
use DateTimeZone;
use DvsaLogger\Contract\IdentityProviderInterface;
use DvsaLogger\Contract\TokenServiceInterface;
use DvsaLogger\Helper\FilteredStackTrace;
use DvsaLogger\Helper\UuidGeneratorTrait;
use DvsaLogger\Processor\DvsaMetadataProcessor;
use Monolog\Level;
use Monolog\Logger;
use Random\RandomException;
use RuntimeException;
use Throwable;

/**
 * DVSA MOT logger wrapping Monolog.
*/
class MotLogger
{
    use UuidGeneratorTrait;

    public const ERROR_LOG_LEVEL = 'ERROR';
    public const INFO_LOG_LEVEL = 'INFO';
    public const WARN_LOG_LEVEL = 'WARN';

    private string $token = '';
    private string $traceId = '';
    private string $spanId = '';
    private string $parentSpanId = '';
    private string $logEntryType = 'General';
    private string $requestUuid = '';

    /**
     * @throws RandomException
     */
    public function __construct(
        private readonly Logger $logger,
        private readonly ?IdentityProviderInterface $identityProvider = null,
        private readonly ?TokenServiceInterface $tokenService = null,
        ?string $requestUuid = null,
        private bool $includeToken = true,
    ) {
        $this->requestUuid = $requestUuid ?? $this->generateUuid();

        $hasMetadataProcessor = false;
        foreach ($logger->getProcessors() as $processor) {
            if ($processor instanceof DVsaMetaDataProcessor) {
                $hasMetadataProcessor = true;
                break;
            }
        }

        if (!$hasMetadataProcessor) {
            $logger->pushProcessor(new DvsaMetadataProcessor());
        }
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }

    public function closeHandlers(): void
    {
        foreach ($this->logger->getHandlers() as $handler) {
            $handler->close();
        }
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setTraceId(string $traceId): void
    {
        $this->traceId = $traceId;
    }

    public function getTraceId(): string
    {
        return $this->traceId;
    }

    public function setSpanId(string $spanId): self
    {
        $this->spanId = $spanId;
        return $this;
    }

    public function getSpanId(): string
    {
        return $this->spanId;
    }

    public function setParentSpanId(string $parentSpanId): self
    {
        $this->parentSpanId = $parentSpanId;
        return $this;
    }

    public function getParentSpanId(): string
    {
        return $this->parentSpanId;
    }

    public function setLogEntryType(string $logEntryType): void
    {
        $this->logEntryType = $logEntryType;
    }

    public function getLogEntryType(): string
    {
        return $this->logEntryType;
    }

    public function getRequestUuid(): string
    {
        return $this->requestUuid;
    }

    public function debug(string $message, array $context = []): self
    {
        return $this->log(Level::Debug, $message, $context);
    }

    public function info(string $message, array $context = []): self
    {
        return $this->log(Level::Info, $message, $context);
    }

    public function notice(string $message, array $context = []): self
    {
        return $this->log(Level::Notice, $message, $context);
    }

    public function warn(string $message, array $context = []): self
    {
        return $this->log(Level::Warning, $message, $context);
    }

    /**
     * @deprecated Backward compatible version for Laminas convention.
     * Use error() instead, which accepts both Monolog Level enums and legacy integer priorities.
     */
    public function err(string $message, array $context = []): self
    {
        return $this->error($message, $context);
    }

    public function error(string $message, array $context = []): self
    {
        return $this->log(Level::Error, $message, $context);
    }

    public function crit(string $message, array $context = []): self
    {
        return $this->log(Level::Critical, $message, $context);
    }

    public function alert(string $message, array $context = []): self
    {
        return $this->log(Level::Alert, $message, $context);
    }

    public function emerg(string $message, array $context = []): self
    {
        return $this->log(Level::Emergency, $message, $context);
    }

    /**
     * Core logging method. Accepts either a Monolog Level enum (new API)
     * or an integer priority (legacy Laminas/Log API for BC).
     *
     * @param Level|int $level Monolog level enum or Laminas-style integer priority
     * @param string $message
     * @param array<string, mixed> $context
     * @return self
     */
    public function log(Level|int $level, string $message, array $context = []): self
    {
        $levelObj = $level instanceof Level
            ? $level
            : match ($level) {
                0 => Level::Emergency,
                1 => Level::Alert,
                2 => Level::Critical,
                3 => Level::Error,
                4 => Level::Warning,
                5 => Level::Notice,
                7 => Level::Debug,
                default => Level::Info,
            };

        $this->doLog($levelObj, $message, $context);
        return $this;
    }

    private function doLog(Level $level, string $message, array $context = []): void
    {
        $metadata = $this->getBasicMetadata($level);

        if (isset($context['ex']) && $context['ex'] instanceof Throwable) {
            $metadata = array_merge($metadata, $this->getExceptionMetadata($context['ex']));
            unset($context['ex']);
        }

        $context['__dvsa_metadata__'] = $metadata;

        $this->logger->log($level, $message, $context);
    }

    /**
     * @throws DateInvalidTimeZoneException
     */
    protected function getBasicMetadata(Level $priority): array
    {
        $levelName = $this->transformLogLevelForLogging($priority->name);

        $identity = $this->identityProvider?->getIdentity();
        $username = $identity?->getUsername() ?? '';
        $microtime = microtime();

        $token = $this->token;
        if ($token === '') {
            try {
                $token = $this->tokenService?->getToken() ?? '';
            } catch (Throwable $exception) {
                error_log(sprintf(
                    'Failed to find a valid token for logging level "%s": %s',
                    $levelName,
                    $exception->getMessage()
                ));
            }
        }

        $metadata = [
            'username' => $username,
            'traceId' => $this->traceId,
            'spanId' => $this->spanId,
            'parentSpanId' => $this->parentSpanId,
            'logEntryType' => $this->logEntryType,
            'microtimeTimestamp' => $this->getMicrosecondsTimestamp($microtime),
            'timestamp' => $this->getTimestamp($microtime),
            'callerName' => $this->getCallerName(),
            'logger_name' => $this->getCallerName(),
            'level' => $levelName,
            'requestUuid' => $this->requestUuid,
        ];

        if ($this->includeToken) {
            $metadata['token'] = $token;
        }

        return $metadata;
    }

    protected function getExceptionMetadata(Throwable $ex): array
    {
        return [
            'logEntryType' => 'Exception',
            'callerName' => $this->getCallerName($ex),
            'logger_name' => $this->getCallerName($ex),
            'stackTrace' => (new FilteredStackTrace())->getTraceAsString($ex),
            'errorCode' => $ex->getCode(),
            'exceptionType' => get_class($ex),
        ];
    }

    protected function getCallerName(?Throwable $ex = null): string
    {
        if ($ex !== null && isset($ex->getTrace()[0])) {
            return $this->formatTraceCaller($ex->getTrace()[0]);
        }

        $trace = debug_backtrace();
        for ($i = 3; $i < count($trace); $i++) {
            if (isset($trace[$i]['class']) && str_starts_with($trace[$i]['class'], 'DvsaLogger\\Logger\\')) {
                continue;
            }
            return $this->formatTraceCaller($trace[$i] ?? []);
        }

        return 'unknown';
    }

    /**
     * @param array<string, mixed> $traceEntry
     * @return string
     */
    protected function formatTraceCaller(array $traceEntry): string
    {
        if (isset($traceEntry['class'])) {
            return $traceEntry['class'] . '\\' . $traceEntry['function'];
        }
        return $traceEntry['function'] ?? 'unknown';
    }

    private function createDateTimeFromMicrotime(string $microtime): DateTimeImmutable
    {
        [$usec, $sec] = explode(' ', $microtime);
        $dateTime = DateTimeImmutable::createFromFormat(
            'U.u',
            sprintf('%d.%06d', (int)$sec, (int)substr($usec, 2, 6)),
        );
        if ($dateTime === false) {
            throw new RuntimeException('Unable to parse microtime.');
        }
        return $dateTime;
    }

    protected function getMicrosecondsTimestamp(string $microtime): string
    {
        $dateTime = $this->createDateTimeFromMicrotime($microtime);
        return $dateTime->format('Y-m-d H:i:s.u\Z');
    }

    protected function getTimestamp(string $microtime): string
    {
        $dateTime = $this->createDateTimeFromMicrotime($microtime);
        // Only 3 digits for milliseconds
        return $dateTime->format('Y-m-d\TH:i:s.')
            . substr($dateTime->format('u'), 0, 3)
            . $dateTime->format('P');
    }

    /**
     *  Maps Monolog level names to DVSA log levels.
     * @param string $level
     * @return string
     */
    private function transformLogLevelForLogging(string $level): string
    {
        return match ($level) {
            'critical', 'emergency', 'error' => self::ERROR_LOG_LEVEL,
            'notice', 'info', 'debug' => self::INFO_LOG_LEVEL,
            'alert', 'warning' => self::WARN_LOG_LEVEL,
            default => strtoupper($level),
        };
    }
}
