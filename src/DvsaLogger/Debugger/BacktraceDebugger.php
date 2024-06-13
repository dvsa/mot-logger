<?php

namespace DvsaLogger\Debugger;

class BacktraceDebugger
{
    /**
     * This method matches a call based on a name being part of class' name.
     *
     * Unfortunately matching based on an interface wasn't sufficient,
     * as this was mainly developed for repository and not all of our repositories
     * implement a common interface.
     *
     * @param string $name
     * @param array  $backtrace
     *
     * @return Call|null
     */
    public function findCall($name, array $backtrace)
    {
        foreach ($backtrace as $item) {
            if (isset($item['object']) && false !== strpos(get_class($item['object']), $name)) {
                return new Call(get_class($item['object']), $item['function']);
            }
            if (isset($item['class']) && false !== strpos($item['class'], $name)) {
                return new Call($item['class'], $item['function']);
            }
        }
    }
}
