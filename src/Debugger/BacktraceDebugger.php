<?php

declare(strict_types=1);

namespace DvsaLogger\Debugger;

class BacktraceDebugger
{
    /**
     * Matches a call based on a name being part of class' name.
     */
    public function findCall(string $name, array $backtrace): ?Call
    {
        foreach ($backtrace as $item) {
            if (!isset($item['object'])) {
                return null;
            }

            $class = get_class($item['object']);
            if (false !== strpos($class, $name)) {
                return new Call($class, $item['function']);
            }
            if (isset($item['class']) && false !== strpos($item['class'], $name)) {
                return new Call($item['class'], $item['function']);
            }
        }

        return null;
    }
}
