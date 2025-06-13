<?php

namespace Bermuda\Router\Collector;

use Bermuda\Router\CompilerInterface;

/**
 * Token collection for route parameter patterns
 *
 * Manages regex patterns for route parameters, combining default patterns
 * from CompilerInterface with custom patterns provided during construction.
 */
final class Tokens extends Collector
{

    /**
     * Get token pattern by name with optional default
     *
     * Checks custom patterns first, then falls back to default patterns
     * from CompilerInterface, and finally to the provided default value.
     *
     * @param string $name Token name to retrieve
     * @param string|null $default Default pattern if token not found
     * @return string|null Token pattern or default value
     */
    public function get(string $name, mixed $default = null): ?string
    {
        if (isset($this->values[$name])) {
            return $this->values[$name];
        }

        return CompilerInterface::DEFAULT_PATTERNS[$name] ?? $default;
    }

    /**
     * Check if token pattern exists in custom or default patterns
     *
     * @param string $name Token name to check
     * @return bool True if token exists in any pattern source
     */
    public function has(string $name): bool
    {
        return isset($this->values[$name]) || isset(CompilerInterface::DEFAULT_PATTERNS[$name]);
    }
}