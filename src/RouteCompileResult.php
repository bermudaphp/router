<?php

namespace Bermuda\Router;

use Bermuda\Stdlib\NumberConverter;

/**
 * Represents the result of compiling a route pattern.
 *
 * This class encapsulates the compiled regular expression and metadata
 * about the route parameters. It provides methods for URL matching and
 * parameter extraction from matched URLs.
 *
 * @example
 * ```php
 * $compiler = new Compiler([]);
 * $result = $compiler->compile('/api/users/[id]/posts/[?slug]');
 *
 * // Check if URL matches the pattern and extract parameters
 * $params = $result->matches('/api/users/123/posts/hello-world');
 * if ($params !== null) {
 *     // $params = ['id' => 123, 'slug' => 'hello-world']
 *     // Note: numeric strings are automatically converted to numbers
 * }
 *
 * // Access compiled information
 * $regex = $result->regex;                    // The compiled regex
 * $allParams = $result->parameters;           // All parameter names
 * $optionalParams = $result->optionalParameters; // Optional parameter names
 * ```
 */
class RouteCompileResult
{
    /**
     * Creates a new route compile result.
     *
     * @param string $regex The compiled regular expression pattern
     * @param array<string> $parameters List of all parameter names in order
     * @param array<string> $optionalParameters List of optional parameter names
     *
     * @example
     * ```php
     * $result = new RouteCompileResult(
     *     '/^\/api\/(?P<resource>[^\/]+)\/(?P<id>\d+)(?:\/(?P<format>[^\/]+))?$/',
     *     ['resource', 'id', 'format'],
     *     ['format']
     * );
     * ```
     */
    public function __construct(
        public readonly string $regex,
        public readonly array $parameters,
        public readonly array $optionalParameters
    ) {}

    /**
     * Matches a URL against this compiled route pattern and extracts parameters.
     *
     * This method tests the provided URL path against the compiled regular expression for the route and,
     * if successful, extracts the named parameters from the match. It combines matching and parameter extraction
     * into a single operation for improved performance.
     *
     * The extracted parameters undergo automatic type conversion:
     * - Numeric strings are converted to integers or floats.
     * - Non-numeric strings remain as strings.
     * - Default values are applied exclusively for optional parameters declared in the route pattern
     *   when they are missing from the provided URI. If an optional parameter is present in the URI,
     *   its value will override the default.
     *
     * Time Complexity: O(n) where n is the length of the URL (regex matching)
     * Space Complexity: O(p) where p is the number of parameters
     *
     * @param string $path The URL path to test for matching.
     * @param array $defaults Default values for optional parameters; these values are applied only if the
     *                        corresponding optional parameter (as declared in the route pattern) is missing
     *                        in the URI.
     *
     * @return array|null An associative array of parameter names to their converted values, or null if
     *                    the URL does not match the pattern.
     * @throws MatchException When regex matching fails or parameter extraction encounters errors.
     *
     * @example
     * ```php
     * $result = $compiler->compile('/api/users/[id]/posts/[?slug]');
     *
     * try {
     *     // Successful match with type conversion.
     *     $params = $result->matches('/api/users/123/posts/hello-world');
     *     // Result: ['id' => 123, 'slug' => 'hello-world']
     *
     *     // Match with optional 'slug' parameter missing.
     *     $params = $result->matches('/api/users/456/posts');
     *     // Result: ['id' => 456, 'slug' => null]
     *
     *     // Match with defaults applied for missing optional parameters.
     *     $params = $result->matches('/api/users/789/posts', ['slug' => 'default']);
     *     // Result: ['id' => 789, 'slug' => 'default']
     *
     *     // No match.
     *     $params = $result->matches('/api/posts/123');
     *     // Result: null
     * } catch (MatchException $e) {
     *     // Handle matching errors with detailed context.
     *     error_log("Pattern matching failed: {$e->getMessage()}");
     * }
     * ```
     */
    public function matches(string $path, array $defaults = []): ?array
    {
        return _match($this->regex, $path, $this->parameters, $defaults);
    }

    /**
     * Determines if this route contains any parameters.
     *
     * Checks whether the compiled route pattern contains any parameters
     * (both required and optional). This is useful for optimization -
     * non-parametrized routes can be matched using simple string comparison
     * instead of regular expressions.
     *
     * Time Complexity: O(1)
     * Space Complexity: O(1)
     *
     * @return bool True if the route has parameters, false for static routes
     *
     * @example
     * ```php
     * // Static route without parameters
     * $result = $compiler->compile('/api/health');
     * $result->isParametrized();  // false
     *
     * // Route with required parameters
     * $result = $compiler->compile('/api/users/[id]');
     * $result->isParametrized();  // true
     *
     * // Route with optional parameters only
     * $result = $compiler->compile('/api/users/[?page]');
     * $result->isParametrized();  // true
     *
     * // Optimization usage
     * if (!$result->isParametrized()) {
     *     // Use fast string comparison
     *     $matches = ($path === '/api/health') ? [] : null;
     * } else {
     *     // Use regex matching with parameter extraction
     *     $matches = $result->matches($path);
     * }
     * ```
     */
    public function isParametrized(): bool
    {
        return !empty($this->parameters);
    }

    /**
     * Check if a URL matches this pattern without extracting parameters.
     *
     * Performs a quick boolean check to determine if the URL matches
     * the route pattern without the overhead of parameter extraction.
     * This is useful when you only need to know if a route matches.
     *
     * @param string $path The URL path to test
     * @return bool True if the URL matches the pattern, false otherwise
     *
     * @example
     * ```php
     * $result = $compiler->compile('/api/users/[id]');
     *
     * if ($result->testMatch('/api/users/123')) {
     *     // Route matches, now extract parameters if needed
     *     $params = $result->matches('/api/users/123');
     * }
     * ```
     */
    public function testMatch(string $path): bool
    {
        return preg_match($this->regex, $path) === 1;
    }
}