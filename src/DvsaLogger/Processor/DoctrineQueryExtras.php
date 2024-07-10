<?php

namespace DvsaLogger\Processor;

use Laminas\Http\Header\Authorization;
use Laminas\Http\Header\GenericHeader;
use Laminas\Http\PhpEnvironment\RemoteAddress;
use Laminas\Http\Request as HttpRequest;
use Laminas\Log\Processor\ProcessorInterface;
use Laminas\Stdlib\ParametersInterface;

/**
 * Class DoctrineQueryExtras
 *
 * @package DvsaLogger\Processor
 */
class DoctrineQueryExtras implements ProcessorInterface
{
    /** @var HttpRequest */
    protected $request;
    /** @var string */
    protected $requestUuid;

    public function __construct(HttpRequest $request, string $uuid)
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
        $header = $this->request->getHeader('X-calling-uri');
        $requesting_page = '';
        if ($header instanceof GenericHeader) {
            $requesting_page = $header->getFieldValue();
        }
        $header = $this->request->getHeader('X-request-uuid');
        $remote_request_uuid = '';
        if ($header instanceof GenericHeader) {
            $remote_request_uuid = $header->getFieldValue();
        }
        $header = $this->request->getHeader('Authorization');
        $token = '';
        if ($header instanceof Authorization) {
            $token = $header->getFieldValue();
        }
        // get request uri and IP address and add it to the extras of the logger
        $remoteAddress = new RemoteAddress();
        /** @var ParametersInterface<?string, ?mixed> */
        $query = $this->request->getQuery();
        $query_string = json_encode($query->toArray());
        /** @var string */
        $content = $this->request->getContent();
        $post_data = json_encode(json_decode($content));
        $method = $this->request->getMethod();
        $extras = array(
            'api_endpoint_uri'    => $uri,
            'api_query_string'    => $query_string,
            'api_post_data'       => $post_data,
            'api_method'          => $method,
            'ip'                  => $remoteAddress->getIpAddress(),
            'session_id'          => session_id(),
            'cookie'              => json_encode($_COOKIE),
            'token'               => $token,
            'remote_request_uri'  => $requesting_page,
            'api_request_uuid'    => $this->requestUuid,
            'remote_request_uuid' => $remote_request_uuid
        );
        if (isset($event['extra']) && is_array($event['extra'])) {
            $extras = array_merge($event['extra'], $extras);
        }
        $event['extra'] = $extras;
        // check if we have trace, else get it explicitly
        return $event;
    }
}
