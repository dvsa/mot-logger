<?php
return [
    'service_manager' => [
        'factories' => [
            'DvsaLogger\FrontendRequestLogger'      => 'DvsaLogger\Factory\FrontendRequestLoggerFactory',
            'DvsaLogger\ApiClientLogger'            => 'DvsaLogger\Factory\ApiClientLoggerFactory',
            'DvsaLogger\ExtrasProcessor'            => 'DvsaLogger\Factory\ExtrasFactory',
            'DvsaLogger\ApiResponseExtras'          => 'DvsaLogger\Factory\ApiResponseExtrasFactory',
            'DvsaLogger\ApiResponseLogger'          => 'DvsaLogger\Factory\ApiResponseLoggerFactory',
            'DvsaLogger\DoctrineQueryLogger'        => 'DvsaLogger\Factory\DoctrineQueryLoggerFactory',
            'DvsaLogger\DoctrineQueryLoggerService' => 'DvsaLogger\Factory\DoctrineQueryLoggerServiceFactory',
            'DvsaLogger\DoctrineQueryExtras'        => 'DvsaLogger\Factory\DoctrineQueryExtrasFactory',
            'DvsaLogger\ApiRequestLogger'           => 'DvsaLogger\Factory\ApiRequestLoggerFactory',
            'DvsaLogger\ApiRequestExtras'           => 'DvsaLogger\Factory\ApiRequestExtrasFactory',
        ],
    ],
    'DvsaLogger'      => [
        'RequestUUID' => uniqid(),
        'dbConfig'    => [
            'driver'   => 'Pdo',
            'dsn'      => 'mysql:dbname=dvsa_logger;host=localhost',
            'username' => 'root',
            'password' => 'password'
        ],
        'listeners'   => [
            'frontend_request'   => [
                'listenerClass' => 'DvsaLogger\Listener\FrontendRequest',
                'loggerFactory' => 'DvsaLogger\FrontendRequestLogger',
                'enabled'       => false,
                'options'       => [
                    'tableName' => 'frontend_request',
                    'columnMap' => [
                        'timestamp'    => 'timestamp',
                        'priority'     => 'priority',
                        'priorityName' => 'priorityName',
                        'message'      => 'message',
                        'extra'        => [
                            'request_uuid'   => 'request_uuid',
                            'username'       => 'username',
                            'php_session_id' => 'php_session_id',
                            'user_agent'     => 'user_agent',
                            'ip_address'     => 'ip_address',
                            'uri'            => 'uri',
                            'request_method' => 'request_method',
                            'route'          => 'route',
                            'parameters'     => 'parameters',
                            'token'          => 'token',
                            'memory_usage'   => 'memory_usage',
                        ],
                    ],
                ],
            ],
            'api_client_request' => [
                'enabled'       => false,
                'listenerClass' => 'DvsaLogger\Listener\ApiClientRequest',
                'loggerFactory' => 'DvsaLogger\ApiClientLogger',
                'options'       => [
                    'tableName' => 'api_client_request',
                    'columnMap' => [
                        'timestamp'    => 'timestamp',
                        'priority'     => 'priority',
                        'priorityName' => 'priorityName',
                        'message'      => 'message',
                        'extra'        => [
                            'endpoint_uri'   => 'endpoint_uri',
                            'request_method' => 'request_method',
                            'parameters'     => 'parameters',
                            'request_uuid'   => 'request_uuid',
                        ],
                    ],
                ],
            ],
            'api_request'        => [
                'listenerClass' => 'DvsaLogger\Listener\ApiRequest',
                'loggerFactory' => 'DvsaLogger\ApiRequestLogger',
                'enabled'       => false,
                'options'       => [
                    'tableName' => 'api_request',
                    'columnMap' => [
                        'timestamp'    => 'timestamp',
                        'priority'     => 'priority',
                        'priorityName' => 'priorityName',
                        'message'      => 'message',
                        'extra'        => [
                            'api_request_uuid'      => 'api_request_uuid',
                            'uri'                   => 'uri',
                            'request_method'        => 'request_method',
                            'parameters'            => 'parameters',
                            'token'                 => 'token',
                            'frontend_request_uuid' => 'frontend_request_uuid'
                        ],
                    ],
                ],
            ],
            'api_response'       => [
                'listenerClass' => 'DvsaLogger\Listener\ApiResponse',
                'loggerFactory' => 'DvsaLogger\ApiResponseLogger',
                'enabled'       => false,
                'options'       => [
                    'tableName' => 'api_response',
                    'columnMap' => [
                        'timestamp'    => 'timestamp',
                        'priority'     => 'priority',
                        'priorityName' => 'priorityName',
                        'message'      => 'message',
                        'extra'        => [
                            'api_request_uuid'      => 'api_request_uuid',
                            'status_code'           => 'status_code',
                            'response_content'      => 'response_content',
                            'content_type'          => 'content_type',
                            'token'                 => 'token',
                            'frontend_request_uuid' => 'frontend_request_uuid',
                            'execution_time'        => 'execution_time',
                        ],
                    ],
                ],
            ],
        ],
        'loggers'     => [
            'doctrine_query' => [
                'enabled' => false,
                'options' => [
                    'tableName' => 'doctrine_query',
                    'columnMap' => [
                        'timestamp'    => 'timestamp',
                        'priority'     => 'priority',
                        'priorityName' => 'priorityName',
                        'message'      => 'message',
                        'extra'        => [
                            'endpoint_uri'     => 'endpoint_uri',
                            'request_method'   => 'request_method',
                            'parameters'       => 'parameters',
                            'api_request_uuid' => 'api_request_uuid',
                            'query'            => 'query',
                            'query_time'       => 'query_time',
                            'context'          => 'context',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
