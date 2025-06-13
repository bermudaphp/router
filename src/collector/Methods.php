<?php

namespace Bermuda\Router\Collector;

use Bermuda\Router\HttpMethod;

/**
 * HTTP methods collection for route handling
 *
 * Manages HTTP methods for routes with automatic normalization.
 * If no methods specified, defaults to all standard HTTP methods.
 */
final class Methods extends Collector
{
    /**
     * Initialize methods collection with normalization
     *
     * Normalizes provided HTTP methods to uppercase. If no methods
     * are provided, defaults to all standard HTTP methods.
     *
     * @param string[] $values Array of HTTP method names to normalize
     */
    public function __construct(array $values = [])
    {
        if ($values !== []) {
            $normalized = array_map(HttpMethod::normalize(...), $values);
        } else {
            $normalized = HttpMethod::all();
        }

        parent::__construct($normalized);
    }

    /**
     * Get method by array index with optional default
     *
     * @param string $name Array index to retrieve
     * @param string|null $default Default value if index not found
     * @return string|null HTTP method at index or default value
     */
    public function get(string $name, mixed $default = null): ?string
    {
        return $this->values[$name] ?? $default;
    }

    /**
     * Check if method is supported by this collection
     *
     * Normalizes the method name before checking for case-insensitive
     * matching against the stored methods.
     *
     * @param string $method HTTP method to check
     * @return bool True if method is supported
     */
    public function has(string $method): bool
    {
        return in_array(HttpMethod::normalize($method), $this->values, true);
    }
}