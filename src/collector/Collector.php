<?php

namespace Bermuda\Router\Collector;

use Bermuda\Stdlib\Arrayable;

/**
 * Base Collection Class for Router Data Structures
 *
 * Abstract base class providing common functionality for all router collections.
 * Implements standard PHP interfaces for iteration, counting, and array conversion,
 * making collections behave like native PHP arrays while maintaining encapsulation.
 *
 * This class serves as the foundation for specialized collections like Parameters,
 * Methods, and Tokens, providing consistent behavior across the router system.
 *
 * Features:
 * - Immutable value storage with controlled access
 * - Iterator support for foreach loops
 * - Countable interface for count() function
 * - Array conversion capabilities
 * - Fluent interface for creating modified instances
 *
 * @internal This class is intended for internal use within the router package
 */
class Collector implements \IteratorAggregate, Arrayable, \Countable
{
    /**
     * Initialize the collection with values
     *
     * Stores the provided array values in an immutable manner. Once created,
     * the values cannot be modified directly, ensuring data integrity and
     * preventing accidental mutations during routing operations.
     *
     * Child classes may override the constructor to provide specialized
     * initialization logic, such as value normalization or validation.
     *
     * @param array $values Array of values to store in the collection
     *
     * @example
     * ```php
     * $collector = new Collector(['key1' => 'value1', 'key2' => 'value2']);
     * ```
     */
    public function __construct(
        protected readonly array $values
    ) {
    }

    /**
     * Convert collection to plain PHP array
     *
     * Returns the internal values array, providing a way to access
     * all collection data as a standard PHP array. This is useful
     * for serialization, debugging, or integration with code that
     * expects array input.
     *
     * The returned array is a copy, preserving the immutability
     * of the original collection values.
     *
     * @return array Copy of internal values array
     *
     * @example
     * ```php
     * $collector = new Collector(['a' => 1, 'b' => 2]);
     * $array = $collector->toArray(); // Returns ['a' => 1, 'b' => 2]
     * ```
     */
    public function toArray(): array
    {
        return $this->values;
    }

    /**
     * Check if collection contains a specific key
     *
     * Determines whether the collection contains an entry with the
     * specified key. This method provides a safe way to check for
     * key existence before attempting to retrieve values.
     *
     * Uses isset() for efficient key checking, which is faster
     * than array_key_exists() for most use cases.
     *
     * @param string $name Key name to check for existence
     * @return bool True if key exists, false otherwise
     *
     * @example
     * ```php
     * $collector = new Collector(['id' => 123, 'name' => 'test']);
     * var_dump($collector->has('id'));    // true
     * var_dump($collector->has('email')); // false
     * ```
     */
    public function has(string $name): bool
    {
        return isset($this->values[$name]);
    }

    /**
     * Create new collection instance with different values
     *
     * Returns a new instance of the same collection class with the
     * provided values, maintaining immutability by not modifying
     * the current instance. This supports functional programming
     * patterns and safe collection transformations.
     *
     * The method uses late static binding to ensure the correct
     * child class is instantiated when called on subclasses.
     *
     * @param array $values New values for the collection instance
     * @return static New collection instance with provided values
     *
     * @example
     * ```php
     * $original = new Collector(['a' => 1]);
     * $modified = $original->with(['b' => 2]); // Original unchanged
     * // $original still contains ['a' => 1]
     * // $modified contains ['b' => 2]
     * ```
     */
    public function with(array $values): static
    {
        return new static($values);
    }

    /**
     * Retrieve value by key with optional default
     *
     * Returns the value associated with the specified key, or the
     * default value if the key doesn't exist. This method provides
     * safe access to collection values without risk of undefined
     * key errors.
     *
     * Child classes may override this method to provide specialized
     * behavior, such as type conversion or additional validation.
     *
     * @param string $name Key name to retrieve
     * @param mixed|null $default Default value if key not found
     * @return mixed Value associated with key or default value
     *
     * @example
     * ```php
     * $collector = new Collector(['id' => 123]);
     * $id = $collector->get('id');           // Returns 123
     * $name = $collector->get('name', 'N/A'); // Returns 'N/A' (default)
     * ```
     */
    public function get(string $name, mixed $default = null): mixed
    {
        return $this->values[$name] ?? $default;
    }

    /**
     * Get iterator for foreach loop support
     *
     * Implements IteratorAggregate interface to enable foreach iteration
     * over collection values. Uses Generator for memory-efficient iteration
     * that doesn't require creating a separate Iterator object.
     *
     * This allows collections to be used naturally in foreach loops
     * while maintaining encapsulation of internal storage.
     *
     * @return \Generator Iterator yielding key-value pairs from collection
     *
     * @example
     * ```php
     * $collector = new Collector(['a' => 1, 'b' => 2]);
     * foreach ($collector as $key => $value) {
     *     echo "$key: $value\n"; // Outputs: a: 1, b: 2
     * }
     * ```
     */
    public function getIterator(): \Generator
    {
        yield from $this->values;
    }

    /**
     * Get count of items in collection
     *
     * Implements Countable interface to enable the count() function
     * to work with collection instances. Returns the number of
     * key-value pairs stored in the collection.
     *
     * This allows collections to integrate seamlessly with PHP's
     * built-in functions that expect countable objects.
     *
     * @return int Number of items in the collection
     *
     * @example
     * ```php
     * $collector = new Collector(['a' => 1, 'b' => 2, 'c' => 3]);
     * $itemCount = count($collector); // Returns 3
     *
     * if (count($collector) > 0) {
     *     // Collection has items
     * }
     * ```
     */
    public function count(): int
    {
        return count($this->values);
    }
}
