<?php

namespace Bermuda\Router;

use Bermuda\Stdlib\NumberConverter;

/**
 * Internal function for matching URL paths against regex patterns and extracting parameters.
 *
 * This function provides centralized logic for URL matching and parameter extraction with automatic
 * type conversion. It is used internally by RouteCompileResult and other routing components to ensure
 * consistent behavior across the routing system.
 *
 * Key Features:
 * - Regex-based URL matching with named capture groups.
 * - Automatic extraction of parameters from matched groups.
 * - Type conversion using NumberConverter for numeric values.
 * - Default value application exclusively for optional parameters explicitly declared in the pattern
 *   when they are missing from the provided URI.
 * - Null handling for empty or missing parameter values.
 * - Comprehensive error handling with MatchException for debugging.
 *
 * Parameter Processing:
 * 1. Extract named groups from regex match.
 * 2. Apply default values only to optional parameters defined in the pattern that are absent in the URI.
 *    If an optional parameter is provided in the URI, its value overrides the default.
 * 3. Convert numeric strings to appropriate types (int/float).
 * 4. Preserve non-numeric strings as-is.
 * 5. Return an associative array mapping parameter names to their converted values.
 *
 * Error Handling:
 * - Throws MatchException for regex matching failures or errors during parameter processing.
 * - Wraps exceptions from NumberConverter with additional context.
 * - Provides detailed error information for debugging.
 *
 * @internal This function is for internal use by routing components only.
 *
 * @param string $pattern The compiled regex pattern with named capture groups.
 * @param string $path The URL path to match against the pattern. MUST be already URL-decoded path component from URI.
 * @param array<string> $parameters List of expected parameter names.
 * @param array<string, mixed> $defaults Default values for optional parameters; defaults are applied
 *                                       only when the parameter is missing from the URI.
 *
 * @return array<string, mixed>|null Extracted parameters with type conversion, or null if no match.
 * @throws MatchException When regex matching fails or parameter processing encounters errors.
 *
 * @example
 * ```php
 * // Internal usage example (not for public use)
 * try {
 *     $regex = '/^\/api\/users\/(?P<id>\d+)\/posts\/(?P<slug>[a-z0-9-]+)$/';
 *     $params = _match($regex, '/api/users/123/posts/hello-world', ['id', 'slug']);
 *     // Result: ['id' => 123, 'slug' => 'hello-world']
 * } catch (MatchException $e) {
 *     // Handle matching errors with detailed context
 *     error_log("Route matching failed: {$e->getMessage()}");
 *     error_log("Pattern: {$e->pattern}, Path: {$e->path}");
 * }
 *
 * // With optional parameters and defaults
 * try {
 *     $regex = '/^\/api\/users\/(?P<id>\d+)(?:\/(?P<format>[^\/]+))?$/';
 *     $params = _match($regex, '/api/users/456', ['id', 'format'], ['format' => 'json']);
 *     // Result: ['id' => 456, 'format' => 'json']
 * } catch (MatchException $e) {
 *     // Handle matching errors
 * }
 * ```
 */
function _match(string $pattern, string $path, array $parameters, array $defaults = []): ?array
{
    try {
        // Attempt to match the URL path against the compiled regex pattern
        $matchResult = preg_match($pattern, $path, $matches);

        // Check for regex errors
        if ($matchResult === false) {
            $error = preg_last_error();
            $errorMessages = [
                PREG_NO_ERROR => 'No error',
                PREG_INTERNAL_ERROR => 'Internal PCRE error',
                PREG_BACKTRACK_LIMIT_ERROR => 'Backtrack limit exhausted',
                PREG_RECURSION_LIMIT_ERROR => 'Recursion limit exhausted',
                PREG_BAD_UTF8_ERROR => 'Malformed UTF-8 data',
                PREG_BAD_UTF8_OFFSET_ERROR => 'Bad UTF-8 offset',
                PREG_JIT_STACKLIMIT_ERROR => 'JIT stack limit exhausted'
            ];

            $errorMessage = $errorMessages[$error] ?? "Unknown PCRE error (code: $error)";
            throw new MatchException(
                $pattern,
                $path,
                "Regex matching failed: $errorMessage. This may indicate a malformed regex pattern or an issue with the input path.",
                500
            );
        }

        // No match found - this is normal, not an error
        if ($matchResult === 0) {
            return null;
        }

        // Process matched parameters with error handling
        $extractedParams = [];

        foreach ($parameters as $name) {
            if (isset($matches[$name]) && $matches[$name] !== '') {
                $value = $matches[$name];
            } else {
                $value = $defaults[$name] ?? null;
            }

            // Apply automatic type conversion for numeric values
            // This converts '123' to 123 (int), '45.67' to 45.67 (float)
            // while preserving non-numeric strings like 'hello' as-is
            $extractedParams[$name] = NumberConverter::convertValue($value);
        }

        return $extractedParams;

    } catch (MatchException $e) {
        // Re-throw MatchException without modification
        throw $e;
    } catch (\Throwable $e) {
        // Wrap any other unexpected errors
        throw MatchException::createFromPrevious($pattern, $path, $e);
    }
}