<?php

namespace DvsaLogger\Processor;

use Core\Service\MotFrontendIdentityProvider;
use DvsaAuthentication\Service\WebAccessTokenService;
use Laminas\Http\Header\UserAgent;
use Laminas\Http\PhpEnvironment\RemoteAddress;
use Laminas\Http\Request as HttpRequest;
use Laminas\Log\Processor\ProcessorInterface;
use Laminas\Stdlib\RequestInterface;

/**
 * Class Extras
 *
 * @package DvsaDoctrineLogger\Processor
 */
class Extras implements ProcessorInterface
{
    const URI_MAX_LENGTH        = 255;
    const USER_AGENT_MAX_LENGTH = 255;

    /** @var null|RequestInterface */
    protected $request = null;
    protected $requestUuid;
    /** @var MotFrontendIdentityProvider $identity */
    protected $identity;
    /** @var WebAccessTokenService $tokenService */
    protected $tokenService;
    protected $routeMatch;

    /**
     * @param \Laminas\Stdlib\RequestInterface $request
     * @param \Core\Service\MotFrontendIdentityProvider $identity
     * @param \DvsaAuthentication\Service\WebAccessTokenService $tokenService
     * @param $routeMatch
     * @param $requestUuid
     */
    public function __construct(
        RequestInterface $request, MotFrontendIdentityProvider $identity, WebAccessTokenService $tokenService,
        $routeMatch, $requestUuid)
    {
        $this->request = $request;
        $this->requestUuid = $requestUuid;
        $this->identity = $identity;
        $this->tokenService = $tokenService;
        $this->routeMatch = $routeMatch;
    }

    /**
     * Adds IP, uri and other details to the event extras
     *
     * @param array $event event data
     *
     * @return array event data
     */
    public function process(array $event)
    {
        $uri = '';
        if ($this->request instanceof HttpRequest) {
            $uri = $this->request->getUriString();
        }
        $token = $this->tokenService->getToken();

        // get request uri and IP address and add it to the extras of the logger
        $remoteAddress = new RemoteAddress();
        $parameters = [];
        $parameters['get_vars'] = $this->request->getQuery()->toArray();
        $parameters['post_vars'] = $this->request->getContent();
        $route = '';
        $request_method = $this->request->getMethod();
        $username = '';
        if ($this->identity->getIdentity()) {
            $username = $this->identity->getIdentity()->getUsername();
        }
        $userAgent = '';
        $header = $this->request->getHeader('UserAgent');
        if ($header instanceof UserAgent) {
            $userAgent = $header->getFieldValue();
        }
        $extras = array(
            'uri'            => substr($uri, 0, self::URI_MAX_LENGTH),
            'parameters'     => json_encode($parameters),
            'request_method' => $request_method,
            'ip_address'     => $remoteAddress->getIpAddress(),
            'php_session_id' => session_id(),
            'username'       => $username,
            'route'          => $route,
            'request_uuid'   => $this->requestUuid,
            'token'          => $token,
            'user_agent'     => substr($userAgent, 0, self::USER_AGENT_MAX_LENGTH),
            'memory_usage'   => memory_get_usage(true),
        );
        if (isset($event['extra']) && is_array($event['extra'])) {
            $extras = array_merge($event['extra'], $extras);
        }
        $event['extra'] = $extras;
        // check if we have trace, else get it explicitly
        return $event;
    }
}
