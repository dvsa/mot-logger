<?php

    use DvsaLogger\Contract\IdentityInterface;
    use DvsaLogger\Contract\IdentityProviderInterface;
    use DvsaLogger\Contract\TokenServiceInterface;
    use DvsaLogger\Factory\ApiRequestExtrasProcessorFactory;
    use DvsaLogger\Factory\ApiRequestListenerFactor;
    use DvsaLogger\Factory\ApiResponseExtrasProcessorFactory;
    use DvsaLogger\Factory\ConsoleLoggerFactory;
    use DvsaLogger\Factory\DoctrineQueryExtrasProcessorFactory;
    use DvsaLogger\Factory\MotLoggerFactory;
    use DvsaLogger\Factory\ReplaceTraceArgsProcessorFactory;
    use DvsaLogger\Factory\SapiHelperFactory;
    use DvsaLogger\Factory\SystemLoggerFactory;
    use DvsaLogger\Helper\SapiHelper;
    use DvsaLogger\Listener\ApiClientRequestListener;
    use DvsaLogger\Listener\ApiRequestListener;
    use DvsaLogger\Listener\ExceptionListener;
    use DvsaLogger\Listener\RequestListener;
    use DvsaLogger\Listener\ResponseListener;
    use DvsaLogger\Logger\ConsoleLogger;
    use DvsaLogger\Logger\MotLogger;
    use DvsaLogger\Logger\SystemLogger;
    use DvsaLogger\Processor\ApiRequestExtrasProcessor;
    use DvsaLogger\Processor\ApiResponseExtrasProcessor;
    use DvsaLogger\Processor\DoctrineQueryExtrasProcessor;
    use DvsaLogger\Processor\ExtrasProcessor;
    use DvsaLogger\Processor\ReplaceTraceArgsProcessor;
    use DvsaLogger\Service\DoctrineQueryLoggerService;

    return [
        'mot_logger' => [
            'channel'                   => 'dvsa-mot',

            'request_uuid'              => null,

            'register_error_handler'    => true,

            'include_token'             => false,
            /**
             * Environment-aware log levels.
             *
             * Set 'environment' to the current environment name, or null to auto-detect
             * from APP_ENV env variable.
             *
             * 'environment_levels' provides global per-environment level overrides.
             * These apply to all writers unless a writer has its own 'level' set.
             *
             * Resolution order for each writer:
             *  1. writer['level'][$environment]        (per-writer env array)
             *  2. writer['level']                      (per-writer fixed string)
             *  3. environment_levels[$environment]     (global)
             *  4. 'debug'                              (default)
             *
             * Example:
             *   'environment'   => null,
             *   'environment_levels' => [
             *     'dev' => 'debug',
             *     'int' => 'info',
             *     'prv' => 'warning',
             *     'pre-prod' => 'error',
             *     'prod' => 'critical',
             *   ],
             */
            'environment' => null,
            'environment_levels' => [
                'dev'       => 'debug',
                'int'       => 'info',
                'prv'       => 'warning',
                'pre-prod'  => 'error',
                'prod'      => 'critical',
            ],

            'mask_credentials' => [
                'mask'          => '********',
                'fields'        => ['password', 'pwd', 'pass', 'secret'],
            ],

            'writers' => [
                [
                    'type'          => 'stream',
                    'path'          => '/var/log/dvsa/mot-api.log',
                    'formatter'     => 'pipe',
                    'level'         => 'error',
                    'enabled'       => false,
                ],
                [
                    'type'          => 'stream',
                    'path'          => '/var/log/dvsa/mot-api.json',
                    'formatter'     => 'json',
                    'level'         => 'error',
                    'enabled'       => false,
                ],
                [
                    'type'          => 'stream',
                    'path'          => '/var/log/dvsa/mot-frontend.log',
                    'formatter'     => 'pipe',
                    'level'         => 'error',
                    'enabled'       => false,
                ],
                [
                    'type'          => 'stream',
                    'path'          => '/var/log/dvsa/mot-frontend.json',
                    'formatter'     => 'json',
                    'level'         => 'error',
                    'enabled'       => false,
                ],

                /**
                 * Database writer example. To enable, set 'enabled' => true and provide
                 * a valid Doctrine DBAL connection service name.
                 *
                 * The 'connection' value is a service name resolved from Laminas
                 * ServiceManager at runtime. Register your connection as:
                 *      'doctrine.connection.mot_logger'    => YourConnectionFactory::class
                 *
                 * The column_map supports nested extra fields:
                 *      'extra' =>  ['request_uuid' => 'request_uuid']
                 * maps $record=>extra['request_uuid'] into the 'extra' DB column as JSON.
                 *
                 * Example for frontend_request table:
                 *
                 * 'writers' => [
                 *      [
                 *          'type'  =>  'database',
                 *          'connection'    => 'doctrine.connection.mot_logger',
                 *          'table'         => 'frontend_request',
                 *          'column_map'    => [
                 *              'timestamp'     => 'timestamp',
                 *              'priority'      => 'priority',
                 *              'priorityName'  => 'priorityName',
                 *              'message'       => 'message',
                 *              'extra'         => [
                 *                  'request_uuid'  => 'request_uuid',
                 *                  'username'      => 'username',
                 *                  'ip_address'    => 'ip_address',
                 *                  'uri'           => 'uri',
                 *                  'route'         => 'route',
                 *              ],
                 *          ],
                 *          'level'         => 'info',
                 *          'enabled'       => true,
                 *      ],
                 *  ],
                 */
            ],

            'listeners'   => [
                'frontend_request'   => [
                    'enabled'       => false,
                    'table'         => 'frontend_request',
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
                'api_request'        => [
                    'enabled'       => false,
                    'table'         => 'api_request',
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
                            'frontend_request_uuid' => 'frontend_request_uuid',
                            'ip_address'            => 'ip_address',
                            'user_agent'            => 'user_agent',
                        ],
                    ],
                ],
                'api_response'       => [
                    'enabled'       => false,
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
                'api_client_request' => [
                    'enabled'       => false,
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

            'doctrine_query' => [
                'enabled'       => false,
                'table'         => 'doctrine_query',
                'columnMap'     => [
                    'timestamp'    => 'timestamp',
                    'priority'     => 'priority',
                    'priorityName' => 'priorityName',
                    'message'      => 'message',
                    'extra'        => [
                        'query'         => 'query',
                        'parameters'    => 'parameters',
                        'types'         => 'types',
                        'query_time'    => 'query_time',
                        'context'       => 'context',
                    ],
                ],
            ],
        ],

        'service_manager' => [
            'factories' => [
                MotLogger::class                        => MotLoggerFactory::class,
                ConsoleLogger::class                    => ConsoleLoggerFactory::class,
                SapiHelper::class                       => SapiHelperFactory::class,
                RequestListener::class                  => ApiRequestListenerFactor::class,
                ResponseListener::class                 => ApiRequestListenerFactor::class,
                ExceptionListener::class                => ApiRequestListenerFactor::class,
                ApiRequestListener::class               => ApiRequestListenerFactor::class,
                ApiClientRequestListener::class         => ApiRequestListenerFactor::class,
                DoctrineQueryLoggerService::class       => DoctrineQueryExtrasProcessorFactory::class,

                // Backward compatible factories
                SystemLogger::class                     => SystemLoggerFactory::class,
                ReplaceTraceArgsProcessor::class        => ReplaceTraceArgsProcessorFactory::class,
                ExtrasProcessor::class                  => ReplaceTraceArgsProcessorFactory::class,
                ApiRequestExtrasProcessor::class        => ApiRequestExtrasProcessorFactory::class,
                ApiResponseExtrasProcessor::class       => ApiResponseExtrasProcessorFactory::class,
                DoctrineQueryExtrasProcessor::class     => DoctrineQueryExtrasProcessorFactory::class,
            ],

            'aliases' => [
                // Legacy service names from mot-application-logger
                'Application\Logger'                                        => MotLogger::class,
                'SystemLogLogger'                                           => SystemLogger::class,
                'DvsaApplicationLogger\Log\SystemLogger'                    => SystemLogger::class,
                'DvsaApplicationLogger\Processor\ReplaceTraceArgsProcessor' => ReplaceTraceArgsProcessor::class,

                // Legacy service names from mot-logger
                'DvsaLogger\DoctrineQueryLoggerService'                     => DoctrineQueryLoggerService::class,
                'DvsaLogger\DoctrineQueryLogger'                            => DoctrineQueryLoggerService::class,
                'DvsaLogger\FrontendRequestLogger'                          => RequestListener::class,
                'DvsaLogger\ApiRequestLogger'                               => ApiRequestListener::class,
                'DvsaLogger\ApiResponseLogger'                              => ResponseListener::class,
                'DvsaLogger\ApiClientLogger'                                => ApiClientRequestListener::class,
                'DvsaLogger\ExtrasProcessor'                                => ExtrasProcessor::class,
                'DvsaLogger\ApiRequestExtras'                               => ApiRequestExtrasProcessor::class,
                'DvsaLogger\ApiResponseExtras'                              => ApiResponseExtrasProcessor::class,
                'DvsaLogger\DoctrineQueryExtras'                            => DoctrineQueryExtrasProcessor::class,

                // Legacy service names used by consuming apps
                'tokenService'                                              => TokenServiceInterface::class,
                'MotIdentityProvider'                                       => IdentityProviderInterface::class,

                // Legacy interface namespaces
                'DvsaApplicationLogger\Interfaces\MotIdentityProviderInterface' => IdentityProviderInterface::class,
                'DvsaApplicationLogger\TokenService\TokenServiceInterface'      => TokenServiceInterface::class,
                'DvsaLogger\Interfaces\MotFrontendIdentityProviderInterface'    => IdentityProviderInterface::class,
                'DvsaLogger\Interfaces\IdentityInterface'                       => IdentityInterface::class,
            ],
        ],
    ];
