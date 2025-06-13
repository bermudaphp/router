<?php

namespace Bermuda\Router\Collector;

use Bermuda\Router\CompilerInterface;

/**
 * Token Collection for Route Parameter Patterns
 *
 * Manages regex patterns for route parameters, combining default patterns
 * from CompilerInterface with custom patterns provided during construction.
 * 
 * This collector provides a unified interface for accessing parameter validation
 * patterns, with automatic fallback to predefined patterns when custom patterns
 * are not available.
 * 
 * Example usage:
 * ```php
 * $tokens = new Tokens(['custom' => '[0-9a-f]+']);
 * $pattern = $tokens->get('id'); // Returns '\d+' from default patterns
 * $custom = $tokens->get('custom'); // Returns '[0-9a-f]+'
 * ```
 */
final class Tokens extends Collector
{
    /**
     * Retrieve token pattern by name with optional default fallback
     *
     * Attempts to find the token pattern in the following order:
     * 1. Custom patterns provided during construction
     * 2. Default patterns from CompilerInterface::DEFAULT_PATTERNS
     * 3. The provided default value
     * 
     * This hierarchical lookup ensures that custom patterns take precedence
     * while maintaining compatibility with standard patterns.
     *
     * @param string $name Token name to retrieve (e.g., 'id', 'slug', 'uuid')
     * @param string|null $default Default pattern if token not found in any source
     * @return string|null Token regex pattern or default value, null if neither found
     * 
     * @example
     * ```php
     * $tokens = new Tokens(['custom' => '\w+']);
     * 
     * // Gets predefined pattern
     * $idPattern = $tokens->get('id'); // Returns '\d+'
     * 
     * // Gets custom pattern
     * $customPattern = $tokens->get('custom'); // Returns '\w+'
     * 
     * // Uses default when not found
     * $unknownPattern = $tokens->get('unknown', '.+'); // Returns '.+'
     * ```
     */
    public function get(string $name, ?string $default = null): ?string
    {
        // Check custom patterns first (highest priority)
        if (isset($this->values[$name])) {
            return $this->values[$name];
        }

        // Fallback to default patterns, then to provided default
        return CompilerInterface::DEFAULT_PATTERNS[$name] ?? $default;
    }

    /**
     * Check if a token pattern exists in any pattern source
     *
     * Determines whether a token pattern is available from either:
     * - Custom patterns provided during construction
     * - Default patterns defined in CompilerInterface
     * 
     * This method is useful for validating token availability before
     * attempting to retrieve patterns or for conditional logic.
     *
     * @param string $name Token name to check for existence
     * @return bool True if token exists in custom or default patterns, false otherwise
     * 
     * @example
     * ```php
     * $tokens = new Tokens(['custom' => '\w+']);
     * 
     * var_dump($tokens->has('id'));     // true (from default patterns)
     * var_dump($tokens->has('custom')); // true (from custom patterns)
     * var_dump($tokens->has('missing')); // false (not found anywhere)
     * ```
     */
    public function has(string $name): bool
    {
        return isset($this->values[$name]) || isset(CompilerInterface::DEFAULT_PATTERNS[$name]);
    }
}
