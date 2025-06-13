<?php

namespace Bermuda\Router\Collector;

/**
 * Route parameters collection
 *
 * Stores extracted route parameters with automatic type conversion
 * for numeric values during route matching.
 */
final class Parameters extends Collector
{
    /**
     * Get parameter value by name with type-safe default
     *
     * Returns the parameter value if it exists, otherwise returns the
     * provided default value. Values maintain their automatically
     * converted types from the matching process.
     *
     * @param string $name Parameter name to retrieve
     * @param null|string|int|float $default Default value if parameter not found
     * @return null|string|int|float Parameter value or default
     */
    public function get(string $name, mixed $default = null): null|string|int|float
    {
        return $this->values[$name] ?? $default;
    }
}