<?php

namespace Bermuda\Router\Exception;

/**
 * URL Generator Exception
 *
 * Exception thrown during URL generation operations when routes cannot be properly
 * processed or generated. This includes scenarios such as missing required parameters,
 * invalid route configurations, or other generation-related errors.
 *
 * Common scenarios:
 * - Missing required route parameters during URL generation
 * - Invalid parameter values that cannot be used in URL construction
 * - Route configuration errors that prevent proper URL generation
 */
final class GeneratorException extends RouterException
{
    /**
     * Create exception from a previous exception with additional context.
     *
     * This method allows wrapping lower-level exceptions (such as those from
     * parameter validation or URL building) into a GeneratorException while
     * preserving the original exception chain for debugging purposes.
     *
     * @param \Throwable $previous The original exception that caused the generation failure
     * @param string|null $message Optional custom message, defaults to previous exception message
     * @param int $code Optional error code, defaults to previous exception code
     * @return self New GeneratorException instance wrapping the original exception
     */
    public static function fromPrevious(\Throwable $previous, ?string $message = null, int $code = 0): self
    {
        return new self(
            $message ?? $previous->getMessage(),
            $code ?: $previous->getCode(),
            $previous
        );
    }

    /**
     * Create and throw exception for missing required route parameter.
     *
     * This method is specifically designed for URL generation scenarios where
     * a route requires certain parameters to build a valid URL, but those
     * parameters are missing from the provided parameter array.
     *
     * The exception includes detailed information about which parameter is missing
     * and for which route, making it easier to debug URL generation issues.
     *
     * @param string $routeName The name of the route being generated
     * @param string $parameterName The name of the missing required parameter
     * @param array $providedParams The parameters that were actually provided
     * @param string|null $message Optional custom error message
     * @return never This method never returns - always throws an exception
     * @throws GeneratorException Always throws with details about the missing parameter
     */
    public static function throwForMissingParameter(
        string $routeName,
        string $parameterName,
        array $providedParams = [],
        ?string $message = null
    ): never {
        $defaultMessage = sprintf(
            'Missing required parameter "%s" for route "%s". Provided parameters: [%s]',
            $parameterName,
            $routeName,
            implode(', ', array_keys($providedParams))
        );

        throw new self($message ?? $defaultMessage, 400);
    }

    /**
     * Create and throw exception for multiple missing required parameters.
     *
     * Similar to throwForMissingParameter but handles cases where multiple
     * required parameters are missing, providing a comprehensive error message
     * that lists all missing parameters at once.
     *
     * @param string $routeName The name of the route being generated
     * @param array $missingParams Array of missing parameter names
     * @param array $providedParams The parameters that were actually provided
     * @param string|null $message Optional custom error message
     * @return never This method never returns - always throws an exception
     * @throws GeneratorException Always throws with details about the missing parameters
     */
    public static function throwForMissingParameters(
        string $routeName,
        array $missingParams,
        array $providedParams = [],
        ?string $message = null
    ): never {
        $defaultMessage = sprintf(
            'Missing required parameters [%s] for route "%s". Provided parameters: [%s]',
            implode(', ', $missingParams),
            $routeName,
            implode(', ', array_keys($providedParams))
        );

        throw new self($message ?? $defaultMessage, 400);
    }

    /**
     * Create and throw exception for invalid parameter value.
     *
     * Thrown when a parameter value doesn't meet the requirements for URL generation,
     * such as invalid format, type, or constraints defined by the route pattern.
     *
     * @param string $routeName The name of the route being generated
     * @param string $parameterName The name of the invalid parameter
     * @param mixed $parameterValue The invalid value that was provided
     * @param string|null $reason Optional explanation of why the value is invalid
     * @param string|null $message Optional custom error message
     * @return never This method never returns - always throws an exception
     * @throws GeneratorException Always throws with details about the invalid parameter
     */
    public static function throwForInvalidParameter(
        string $routeName,
        string $parameterName,
        mixed $parameterValue,
        ?string $reason = null,
        ?string $message = null
    ): never {
        $valueString = is_scalar($parameterValue) ? (string) $parameterValue : gettype($parameterValue);
        $reasonString = $reason ? " ($reason)" : '';

        $defaultMessage = sprintf(
            'Invalid parameter "%s" with value "%s" for route "%s"%s',
            $parameterName,
            $valueString,
            $routeName,
            $reasonString
        );

        throw new self($message ?? $defaultMessage, 400);
    }
}