<?php

declare(strict_types=1);

namespace DvsaLogger\Helper;

use Exception;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use Throwable;

class FilteredStackTrace
{
    /**
     * Arguments to hide from stack trace
     */
    private const string TRACE_EXCLUSIONS = "/^(password|pwd|pass|newPassword)$/";

    public function getTraceAsString(Throwable $exception): string
    {
        $trace = $exception->getTrace();
        $traceString = "";
        $count = 0;

        foreach ($trace as $line) {
            $traceString .= $this->getTraceLineAsFilteredString($count, $line);
            $count++;
        }

        return $traceString;
    }

    protected function getTraceLineAsFilteredString(int $count, array $line): string
    {
        $currentFile = $line['file'] ?? '[internal function]';
        $currentLine = $line['line'] ?? '';
        $className = $line['class'] ?? null;
        $function = $line['function'] ?? '';
        $fullyQualifiedFunction = $className !== null ? $className . '->' . $function : $function;

        $canGetArgumentNames = true;
        $argumentNames = [];

        try {
            $argumentNames = $this->getArgumentNames($function, $className);
        } catch (Exception) {
            $canGetArgumentNames = false;
        }

        $argumentsString = "";

        if (isset($line['args'])) {
            $argumentsString = $this->getArguments($line['args'], $argumentNames, $canGetArgumentNames);
        }

        return sprintf(
            "#%s %s(%s): %s(%s)\n",
            $count,
            $currentFile,
            $currentLine,
            $fullyQualifiedFunction,
            $argumentsString
        );
    }

    /**
     * @throws ReflectionException
     */
    protected function getArgumentNames(string $function, ?string $className): array
    {
        $argumentNames = [];

        if ($className !== null && class_exists($className)) {
            $ref = new ReflectionMethod($className, $function);
        } elseif ($className === null && function_exists($function)) {
            $ref = new ReflectionFunction($function);
        } else {
            return $argumentNames;
        }

        foreach ($ref->getParameters() as $parameter) {
            $argumentNames[] = $parameter->getName();
        }
        return $argumentNames;
    }

    protected function getArguments(
        array $argumentValues,
        array $argumentNames,
        bool $canGetArgumentNames,
    ): string {
        $argumentStrings = [];
        $argumentsCount = 0;

        foreach ($argumentValues as $argumentValue) {
            if (is_string($argumentValue)) {
                if ($canGetArgumentNames && isset($argumentNames[$argumentsCount])) {
                    $value = $this->filterArgument($argumentNames[$argumentsCount], $argumentValue);
                } else {
                    $value = "'######'";
                }
            } elseif (is_array($argumentValue)) {
                $value = 'Array';
            } elseif (is_null($argumentValue)) {
                $value = 'NULL';
            } elseif (is_bool($argumentValue)) {
                $value = $argumentValue ? 'true' : 'false';
            } elseif (is_object($argumentValue)) {
                $value = 'Object(' . get_class($argumentValue) . ')';
            } else {
                $value = $argumentValue;
            }

            $argumentStrings[] = isset($argumentNames[$argumentsCount])
                ? $argumentNames[$argumentsCount] . '=' . $value
                : $value;

            $argumentsCount++;
        }

        return join(', ', $argumentStrings);
    }

    /**
     * Filters out arguments that match trace exclusions.
     *
     * @param string $argumentName
     * @param string $argumentValue
     * @return string
     */
    protected function filterArgument(string $argumentName, string $argumentValue): string
    {
        if (preg_match(self::TRACE_EXCLUSIONS, $argumentName)) {
            return "'******'";
        }
        return "'" . str_replace("'", "\\", $argumentValue) . "'";
    }
}
