<?php

namespace Bermuda\Router\Exception;

/**
 * Match Exception
 *
 * Exception thrown during route pattern matching operations when regex compilation,
 * URL matching, or parameter extraction encounters errors. This exception provides
 * detailed context about the matching failure, including the pattern and path
 * that caused the issue.
 *
 * Common scenarios:
 * - Malformed regex patterns causing PCRE errors
 * - Parameter extraction failures during type conversion
 * - Unexpected errors during the matching process
 * - Performance issues with complex regex patterns
 *
 * The exception captures both the pattern and path involved in the failure,
 * making it easier to debug routing issues and identify problematic route
 * configurations.
 */
class MatchException extends RouterException
{
    /**
     * Initialize the match exception with pattern and path context.
     *
     * @param string $pattern The regex pattern that failed to match or caused an error
     * @param string $path The URL path being matched against the pattern
     * @param string $message Descriptive error message explaining the failure
     * @param int $code Error code (defaults to 0)
     * @param \Throwable|null $previous Previous exception in the chain (if any)
     */
    public function __construct(
        public readonly string $pattern,
        public readonly string $path,
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create and immediately throw exception from a previous exception.
     *
     * Convenience method that creates a MatchException from an existing exception
     * and immediately throws it. This method never returns normally.
     *
     * @param string $pattern The regex pattern involved in the failure
     * @param string $path The URL path being matched
     * @param \Throwable $previous The original exception that caused the failure
     * @return never This method never returns - always throws an exception
     * @throws MatchException Always throws with wrapped exception details
     */
    public static function throwFromPrevious(string $pattern, string $path, \Throwable $previous): never
    {
        throw self::createFromPrevious($pattern, $path, $previous);
    }

    /**
     * Create exception from a previous exception with additional context.
     *
     * Factory method that wraps an existing exception in a MatchException,
     * preserving the original exception chain while adding route matching context.
     * This is useful for wrapping lower-level exceptions (like NumberConverter errors)
     * with routing-specific information.
     *
     * @param string $pattern The regex pattern involved in the failure
     * @param string $path The URL path being matched
     * @param \Throwable $previous The original exception that caused the failure
     * @return self New MatchException instance wrapping the original exception
     */
    public static function createFromPrevious(string $pattern, string $path, \Throwable $previous): self
    {
        $message = sprintf(
            'Route matching failed for pattern "%s" and path "%s": %s',
            $pattern,
            $path,
            $previous->getMessage()
        );

        return new self($pattern, $path, $message, $previous->getCode(), $previous);
    }

    /**
     * Create exception for regex compilation errors.
     *
     * Specialized factory method for creating exceptions when regex patterns
     * fail to compile or execute properly. Provides specific messaging for
     * regex-related failures.
     *
     * @param string $pattern The problematic regex pattern
     * @param string $path The URL path being matched
     * @param string $pcreError Specific PCRE error message
     * @return self New MatchException instance for regex errors
     */
    public static function forRegexError(string $pattern, string $path, string $pcreError): self
    {
        $message = sprintf(
            'Regex pattern compilation or execution failed: %s. Pattern: "%s", Path: "%s"',
            $pcreError,
            $pattern,
            $path
        );

        return new self($pattern, $path, $message, 500);
    }

    /**
     * Create exception for parameter extraction errors.
     *
     * Specialized factory method for creating exceptions when parameter
     * extraction or type conversion fails during the matching process.
     *
     * @param string $pattern The regex pattern being used
     * @param string $path The URL path being matched
     * @param string $parameterName The name of the parameter that failed extraction
     * @param mixed $parameterValue The value that failed conversion
     * @param string $reason Additional details about the failure
     * @return self New MatchException instance for parameter errors
     */
    public static function forParameterError(
        string $pattern,
        string $path,
        string $parameterName,
        mixed $parameterValue,
        string $reason
    ): self {
        $valueString = is_scalar($parameterValue) ? (string) $parameterValue : gettype($parameterValue);

        $message = sprintf(
            'Parameter extraction failed for "%s" with value "%s": %s. Pattern: "%s", Path: "%s"',
            $parameterName,
            $valueString,
            $reason,
            $pattern,
            $path
        );

        return new self($pattern, $path, $message, 400);
    }
}