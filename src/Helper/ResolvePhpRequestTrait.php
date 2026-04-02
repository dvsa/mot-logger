<?php

declare(strict_types=1);

namespace DvsaLogger\Helper;

use Laminas\Http\PhpEnvironment\Request;
use Laminas\Stdlib\RequestInterface;

trait ResolvePhpRequestTrait
{
    private function resolvePhpEnvironmentRequest(RequestInterface $request): ?Request
    {
        return $request instanceof Request ? $request : null;
    }
}
