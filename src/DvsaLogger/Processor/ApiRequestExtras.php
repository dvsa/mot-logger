<?php

namespace DvsaLogger\Processor;

use Laminas\Http\Header\Authorization;
use Laminas\Http\Header\GenericHeader;
use Laminas\Http\Header\UserAgent;
use Laminas\Http\PhpEnvironment\RemoteAddress;
use Laminas\Log\Processor\ProcessorInterface;
use Laminas\Http\Request;
use Laminas\Stdlib\ParametersInterface;

/**
 * Class Extras
 *
 * @package DvsaDoctrineLogger\Processor
 */
class ApiRequestExtras implements ProcessorInterface
{
    /** @var Request */
    protected $request;
    /** @var string */
    protected $requestUuid;

    public function __construct(Request $request, string $uuid)
    {
        $this->request = $request;
        $this->requestUuid = $uuid;
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
        $uri = $this->request->getUriString();
        $header = $this->request->getHeader('Authorization');
        $token = '';
        if ($header instanceof Authorization) {
            $token = $header->getFieldValue();
        }

        $header = $this->request->getHeader('X-request-uuid');
        $frontend_request_uuid = '';
        if ($header instanceof GenericHeader) {
            $frontend_request_uuid = $header->getFieldValue();
        }

        // get request uri and IP address and add it to the extras of the logger
        $remoteAddress = new RemoteAddress();
        $parameters = [];
        /** @var ParametersInterface<string, string> */
        $query = $this->request->getQuery();
        $parameters['get_vars'] = $query->toArray();
        $parameters['post_vars'] = $this->request->getContent();
        $route = '';
        $request_method = $this->request->getMethod();
        $user_agent = '';
        $header = $this->request->getHeader('UserAgent');
        if ($header instanceof UserAgent) {
            $user_agent = $header->getFieldValue();
        }
        $extras = array(
            'uri'                   => $uri,
            'parameters'            => json_encode($parameters),
            'request_method'        => $request_method,
            'ip_address'            => $remoteAddress->getIpAddress(),
            'php_session_id'        => session_id(),
            'route'                 => $route,
            'api_request_uuid'      => $this->requestUuid,
            'frontend_request_uuid' => $frontend_request_uuid,
            'token'                 => $token,
            'user_agent'            => $user_agent,
        );
        if (isset($event['extra']) && is_array($event['extra'])) {
            $extras = array_merge($event['extra'], $extras);
        }
        $event['extra'] = $extras;
        // check if we have trace, else get it explicitly
        return $event;
    }
}
