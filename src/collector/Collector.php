<?php

namespace Bermuda\Router\Collector;

use Bermuda\Stdlib\Arrayable;

/**
 * Base collection class for router data structures
 *
 * Provides common functionality for all router collections including
 * iteration, counting, and array conversion capabilities.
 *
 * @internal
 */
class Collector implements \IteratorAggregate, Arrayable, \Countable
{
    /**
     * Initialize the collection with values
     *
     * @param array $values Array of values to store in the collection
     */
    public function __construct(
        protected readonly array $values
    ) {
    }

    /**
     * Convert collection to plain PHP array
     *
     * @return array Copy of internal values array
     */
    public function toArray(): array
    {
        return $this->values;
    }

    /**
     * Check if collection contains a specific key
     *
     * @param string $name Key name to check for existence
     * @return bool True if key exists, false otherwise
     */
    public function has(string $name): bool
    {
        return isset($this->values[$name]);
    }

    /**
     * Create new collection instance with different values
     *
     * Returns a new instance maintaining immutability of the current instance.
     *
     * @param array $values New values for the collection instance
     * @return static New collection instance with provided values
     */
    public function with(array $values): static
    {
        return new static($values);
    }

    /**
     * Retrieve value by key with optional default
     *
     * @param string $name Key name to retrieve
     * @param mixed|null $default Default value if key not found
     * @return mixed Value associated with key or default value
     */
    public function get(string $name, mixed $default = null): mixed
    {
        return $this->values[$name] ?? $default;
    }

    /**
     * Get iterator for foreach loop support
     *
     * @return \Generator Iterator yielding key-value pairs from collection
     */
    public function getIterator(): \Generator
    {
        yield from $this->values;
    }

    /**
     * Get count of items in collection
     *
     * @return int Number of items in the collection
     */
    public function count(): int
    {
        return count($this->values);
    }
}