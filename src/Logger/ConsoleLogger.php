<?php

declare(strict_types=1);

namespace DvsaLogger\Logger;

use DvsaLogger\Contract\IdentityProviderInterface;
use DvsaLogger\Contract\TokenServiceInterface;
use DvsaLogger\Formatter\PipeDelimitedFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

class ConsoleLogger extends MotLogger
{
    public function __construct(
        Logger $logger,
        ?IdentityProviderInterface $identityProvider = null,
        ?TokenServiceInterface $tokenService = null,
        ?string $requestUuid = null,
    ) {
        parent::__construct($logger, $identityProvider, $tokenService, $requestUuid);

        $stdout = new StreamHandler('php://output', Level::Debug);
        $stdout->setFormatter(new PipeDelimitedFormatter());
        $this->getLogger()->pushHandler($stdout);
    }

    protected function getBasicMetadata(Level $priority): array
    {
        $metadata = parent::getBasicMetadata($priority);
        $metadata['username'] = '';
        return $metadata;
    }
}
