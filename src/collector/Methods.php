<?php

namespace Bermuda\Router\Collector;

use Bermuda\Router\HttpMethod;

/**
 * HTTP Methods Collection for Route Handling
 *
 * Manages HTTP methods for routes with automatic normalization and validation.
 * If no methods are specified during construction, defaults to all standard 
 * HTTP methods supported by the HttpMethod utility class.
 * 
 * This collection ensures consistent HTTP method handling across the routing
 * system by normalizing method names to uppercase and providing efficient
 * method checking capabilities.
 * 
 * Example usage:
 * ```php
 * $methods = new Methods(['get', 'post']); // Normalized to ['GET', 'POST']
 * $allMethods = new Methods();             // Contains all standard HTTP methods
 * ```
 */
class Methods extends Collector
{
    /**
     * Initialize methods collection with automatic normalization
     *
     * Processes the provided HTTP methods array by normalizing each method
     * name using HttpMethod::normalize(). If no methods are provided (empty array),
     * automatically populates the collection with all standard HTTP methods.
     * 
     * Method normalization ensures consistency by:
     * - Converting to uppercase (e.g., 'get' → 'GET')
     * - Validating method names against standard HTTP methods
     * - Maintaining a clean, predictable method format
     *
     * @param string[] $values Array of HTTP method names to normalize and store
     *                        Empty array results in all standard methods being used
     * 
     * @example
     * ```php
     * // Specific methods (normalized)
     * $methods = new Methods(['get', 'POST', 'patch']);
     * // Results in: ['GET', 'POST', 'PATCH']
     * 
     * // All standard methods
     * $allMethods = new Methods();
     * // Results in: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS']
     * ```
     */
    public function __construct(array $values = [])
    {
        if ($values !== []) {
            // Normalize provided methods to uppercase standard format
            $normalized = array_map(HttpMethod::normalize(...), $values);
        } else {
            // Use all standard HTTP methods when none specified
            $normalized = HttpMethod::all();
        }

        parent::__construct($normalized);
    }

    /**
     * Retrieve method by array index with optional default
     *
     * Accesses HTTP methods by their array index position rather than
     * by method name. This method maintains consistency with the parent
     * Collector interface while providing array-like access patterns.
     * 
     * Note: For checking method existence, use the has() method instead.
     *
     * @param string $name Array index position (as string) to retrieve
     * @param string|null $default Default value if index not found
     * @return string|null HTTP method at specified index or default value
     * 
     * @example
     * ```php
     * $methods = new Methods(['GET', 'POST']);
     * $first = $methods->get('0');  // Returns 'GET'
     * $second = $methods->get('1'); // Returns 'POST'
     * $missing = $methods->get('2', 'PUT'); // Returns 'PUT' (default)
     * ```
     */
    public function get(string $name, ?string $default = null): ?string
    {
        return $this->values[$name] ?? $default;
    }

    /**
     * Check if HTTP method is supported by this collection
     *
     * Determines whether a specific HTTP method is included in this collection.
     * The method name is automatically normalized before checking, ensuring
     * case-insensitive matching and consistent behavior.
     * 
     * This is the preferred method for validating HTTP method support
     * before processing requests or making routing decisions.
     *
     * @param string $method HTTP method to check (case-insensitive)
     * @return bool True if method is supported, false otherwise
     * 
     * @example
     * ```php
     * $methods = new Methods(['GET', 'POST']);
     * 
     * var_dump($methods->has('GET'));    // true
     * var_dump($methods->has('get'));    // true (normalized)
     * var_dump($methods->has('POST'));   // true  
     * var_dump($methods->has('PUT'));    // false
     * var_dump($methods->has('DELETE')); // false
     * 
     * // With all methods
     * $allMethods = new Methods();
     * var_dump($allMethods->has('PUT')); // true (all methods included)
     * ```
     */
    public function has(string $method): bool
    {
        return in_array(HttpMethod::normalize($method), $this->values, true);
    }
}
