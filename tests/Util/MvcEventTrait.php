<?php

declare(strict_types=1);

namespace DvsaLogger\Util;

use Laminas\Http\PhpEnvironment\Request as PhpRequest;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\Parameters;

trait MvcEventTrait
{
    private function createEvent(array $options = [], ?object $request = null): MvcEvent
    {
        if ($request === null) {
            $request = new PhpRequest();
            $request->setUri('http://example.com/api/test');
            $request->setMethod($options['method'] ?? 'GET');

            if (isset($options['query'])) {
                $request->setQuery(new Parameters($options['query']));
            }
            if (isset($options['content'])) {
                $request->setContent($options['content']);
            }
            foreach ($options['headers'] ?? [] as $name => $value) {
                if (method_exists($request, 'getHeaders')) {
                    $request->getHeaders()->addHeaderLine($name, $value);
                }
            }
        }

        $event = new MvcEvent();
        $event->setRequest($request);
        return $event;
    }
}
