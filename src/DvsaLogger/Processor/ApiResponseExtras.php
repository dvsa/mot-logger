<?php

namespace DvsaLogger\Processor;

use Core\Service\MotFrontendIdentityProvider;
use DvsaAuthentication\Service\WebAccessTokenService;
use Laminas\Http\Header\Authorization;
use Laminas\Http\Header\ContentType;
use Laminas\Http\Header\GenericHeader;
use Laminas\Log\Processor\ProcessorInterface;
use Laminas\Stdlib\RequestInterface;
use Laminas\Stdlib\ResponseInterface;

/**
 * Class Extras
 *
 * @package DvsaDoctrineLogger\Processor
 */
class ApiResponseExtras implements ProcessorInterface
{
    /** @var null|RequestInterface */
    protected $request = null;
    protected $requestUuid;
    /** @var MotFrontendIdentityProvider $identity */
    protected $identity;
    /** @var WebAccessTokenService $tokenService */
    protected $tokenService;
    protected $response;

    public function __construct(RequestInterface $request, ResponseInterface $response, $uuid)
    {
        $this->request = $request;
        $this->response = $response;
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

        $parameters = [];
        $parameters['get_vars'] = $this->request->getQuery()->toArray();
        $parameters['post_vars'] = $this->request->getContent();

        $header = $this->response->getHeaders()->get('Content-Type');
        $contentType = '';
        if ($header instanceof ContentType) {
            $contentType = $header->getFieldValue();
        }

        $content = $this->response->getContent();

        $request_start_time = microtime(true);
        if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            $request_start_time = $_SERVER['REQUEST_TIME_FLOAT'];
        }

        $extras = [
            'status_code'           => $this->response->getStatusCode(),
            'content_type'          => $contentType,
            'response_content'      => $content,
            'api_request_uuid'      => $this->requestUuid,
            'frontend_request_uuid' => $frontend_request_uuid,
            'token'                 => $token,
            'execution_time'        => microtime(true) - $request_start_time,
        ];
        if (isset($event['extra']) && is_array($event['extra'])) {
            $extras = array_merge($event['extra'], $extras);
        }
        $event['extra'] = $extras;
        // check if we have trace, else get it explicitly
        return $event;
    }
}
