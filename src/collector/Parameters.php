<?php

namespace Bermuda\Router\Collector;

/**
 * Route Parameters Collection
 *
 * Stores extracted route parameters with automatic type conversion
 * for numeric values during route matching. This collection maintains
 * parameter values extracted from matched route patterns and provides
 * type-safe access to parameter data.
 *
 * Parameters are automatically converted to appropriate types:
 * - String values that represent integers are converted to int
 * - String values that represent floats are converted to float
 * - Other values remain as strings
 *
 * Example usage:
 * ```php
 * $params = new Parameters(['id' => '123', 'slug' => 'hello-world']);
 * $id = $params->get('id');   // Returns (int) 123
 * $slug = $params->get('slug'); // Returns (string) 'hello-world'
 * ```
 */
final class Parameters extends Collector
{
    /**
     * Retrieve parameter value by name with type-safe default
     *
     * Returns the parameter value if it exists, otherwise returns the
     * provided default value. The return type is constrained to prevent
     * type confusion and ensure consistency in parameter handling.
     *
     * Parameter values maintain their automatically converted types:
     * - Numeric strings are converted to int or float during construction
     * - Non-numeric strings remain as strings
     * - Default values should match expected parameter types
     *
     * @param string $name Parameter name to retrieve (e.g., 'id', 'slug', 'page')
     * @param null|string|int|float $default Default value if parameter not found
     * @return null|string|int|float Parameter value with preserved type or default value
     *
     * @example
     * ```php
     * // Route: /users/[id]/posts/[?page]
     * // Matched URL: /users/123/posts/2
     * $params = new Parameters(['id' => 123, 'page' => 2]);
     *
     * $userId = $params->get('id');           // Returns (int) 123
     * $page = $params->get('page');           // Returns (int) 2
     * $limit = $params->get('limit', 10);     // Returns (int) 10 (default)
     * $sort = $params->get('sort', 'name');   // Returns (string) 'name' (default)
     * $missing = $params->get('missing');     // Returns null
     * ```
     */
    public function get(string $name, null|string|int|float $default = null): null|string|int|float
    {
        return $this->values[$name] ?? $default;
    }
}
