<?php

namespace Bermuda\Router;

/**
 * Route compiler interface for transforming route patterns into regular expressions.
 *
 * This interface defines the contract for route compilers that can convert
 * string patterns with parameter tokens into compiled regular expressions
 * suitable for URL matching and parameter extraction.
 *
 */
interface CompilerInterface
{
    /**
     * Default regex patterns for common parameter types.
     *
     * These patterns provide commonly used regular expressions for typical
     * route parameters like IDs, slugs, dates, etc. They can be referenced
     * by parameter name in route patterns.
     *
     * @var array<string, string> Associative array mapping parameter names to regex patterns
     */
    public const array DEFAULT_PATTERNS = [
        'id' => '\d+',                                                              // Numeric ID (1, 123, 999)
        'slug' => '[a-z0-9-]+',                                                     // URL-friendly slug (hello-world, my-post)
        'uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}', // UUID v4 format
        'any' => '.+',                                                              // Any characters including slashes
        'alpha' => '[a-zA-Z]+',                                                     // Letters only (Hello, ABC)
        'alnum' => '[a-zA-Z0-9]+',                                                  // Letters and numbers (Hello123, ABC789)
        'year' => '[12]\d{3}',                                                      // 4-digit year (1900-2999)
        'month' => '0[1-9]|1[0-2]',                                                 // Month (01-12)
        'day' => '0[1-9]|[12]\d|3[01]',                                             // Day of month (01-31)
        'locale' => '[a-z]{2}(_[A-Z]{2})?',                                         // Locale code (en, en_US, fr_FR)
        'version' => 'v?\d+(\.\d+)*',                                               // Version string (1.0, v2.1.3)
        'date' => '\d{4}-\d{2}-\d{2}',                                              // ISO date (2024-12-25)
    ];

    /**
     * Compiles a route pattern into a regular expression with parameter extraction.
     *
     * Takes a route pattern string containing parameter tokens and converts it
     * into a compiled result that can be used for URL matching and parameter
     * extraction.
     *
     * @param string $route The route pattern to compile (e.g., '/api/[resource]/[id]')
     *
     * @return RouteCompileResult The compiled route result containing regex and parameter info
     *
     * @throws \InvalidArgumentException When the route pattern is malformed
     *
     * @example
     * ```php
     * $compiler = new Compiler([]);
     * $result = $compiler->compile('/api/[resource]/[id]');
     *
     * if ($result->matches('/api/users/123')) {
     *     $params = $result->extractParameters('/api/users/123');
     *     // $params = ['resource' => 'users', 'id' => '123']
     * }
     * ```
     */
    public function compile(string $route): RouteCompileResult;

    /**
     * Determines if a route pattern contains parameters without full compilation.
     *
     * Performs a fast check to determine if the given route pattern contains
     * any parameters. This is useful for optimization decisions, allowing
     * static routes to be handled differently from parametrized routes.
     *
     * @param string $route The route pattern to check
     *
     * @return bool True if the route contains parameters, false for static routes
     *
     * @example
     * ```php
     * $compiler = new Compiler([]);
     *
     * $compiler->isParametrized('/api/health');        // false
     * $compiler->isParametrized('/api/users/[id]');    // true
     * $compiler->isParametrized('/api/search/[?q]');   // true
     *
     * // Optimization usage
     * if ($compiler->isParametrized($route)) {
     *     $result = $compiler->compile($route);
     *     $matches = $result->matches($url);
     * } else {
     *     $matches = ($url === $route);
     * }
     * ```
     */
    public function isParametrized(string $route): bool;

    /**
     * Generates a URL from a route template and parameter values.
     *
     * Takes a route pattern and replaces parameter tokens with actual values
     * to generate a concrete URL. Required parameters must be provided,
     * optional parameters are included only if values are given.
     *
     * @param string $template The route pattern template
     * @param array<string, mixed> $parameters Parameter values to substitute
     *
     * @return string The generated URL with parameters substituted
     *
     * @throws \InvalidArgumentException When required parameters are missing
     *
     * @example
     * ```php
     * // Required parameters
     * $url = $compiler->generate('/api/users/[id]', ['id' => 123]);
     * // Result: '/api/users/123'
     *
     * // Optional parameters
     * $url = $compiler->generate('/api/users/[?page]', ['page' => 2]);
     * // Result: '/api/users/2'
     *
     * $url = $compiler->generate('/api/users/[?page]', []);
     * // Result: '/api/users'
     *
     * // Mixed parameters
     * $url = $compiler->generate('/blog/[year]/[slug]/[?format]', [
     *     'year' => 2024,
     *     'slug' => 'hello-world',
     *     'format' => 'json'
     * ]);
     * // Result: '/blog/2024/hello-world/json'
     * ```
     */
    public function generate(string $template, array $parameters): string ;
}