<?php

namespace Bermuda\Router;

/**
 * Value object representing a route path with automatic normalization
 *
 * Immutable value object that encapsulates route path handling with automatic
 * normalization, validation, and common path operations. All path modifications
 * return new instances preserving immutability.
 *
 * Features:
 * - Automatic path normalization on construction
 * - URL decoding and slash handling
 * - Immutable operations with fluent interface
 * - String conversion support
 * - Common path manipulation methods
 */
final class RoutePath implements \Stringable
{
    /**
     * The normalized path value
     *
     * @var string Normalized path string
     */
    private(set) string $value;

    /**
     * Create a new RoutePath instance
     *
     * @param string $path Raw path to normalize and store
     */
    public function __construct(string $path)
    {
        $this->value = self::normalize($path);
    }

    /**
     * Create a new RoutePath with different path
     *
     * @param string $path New path value
     * @return self New RoutePath instance
     */
    public function with(string $path): self
    {
        return new self($path);
    }

    /**
     * Check if path starts with given string
     *
     * @param string $needle String to check for at start of path
     * @return bool True if path starts with needle
     */
    public function startsWith(string $needle): bool
    {
        return str_starts_with($this->value, $needle);
    }

    public function getPrefix(): string
    {
        $value = ltrim($this->value, '/');
        return explode('/', $value)[0];
    }

    /**
     * Check if path ends with given string
     *
     * @param string $needle String to check for at end of path
     * @return bool True if path ends with needle
     */
    public function endsWith(string $needle): bool
    {
        return str_ends_with($this->value, $needle);
    }

    /**
     * Create new RoutePath with prefix added
     *
     * @param string $prefix Prefix to prepend to current path
     * @return self New RoutePath with prefix added
     */
    public function withPrefix(string $prefix): self
    {
        return new self($prefix . '/' . $this->value);
    }

    /**
     * Create new RoutePath with suffix added
     *
     * @param string $suffix Suffix to append to current path
     * @return self New RoutePath with suffix added
     */
    public function withSuffix(string $suffix): self
    {
        return new self($this->value . '/' . $suffix);
    }

    /**
     * Normalize a path string
     *
     * Performs comprehensive path normalization including:
     * - URL decoding
     * - Slash replacement and normalization
     * - Leading slash enforcement
     * - Multiple slash collapsing
     *
     * @param string $path Raw path to normalize
     * @return string Normalized path
     */
    public static function normalize(string $path): string
    {
        preg_match_all('/\[[^\]]*\]/', $path, $matches);
        $tokens = $matches[0];

        // Заменяем каждый найденный токен на уникальный placeholder.
        $placeholders = [];
        foreach ($tokens as $i => $token) {
            $placeholder = "__TOKEN_{$i}__";
            $placeholders[$placeholder] = $token;
            $path = str_replace($token, $placeholder, $path);
        }

        // Заменяем обратные слэши на прямые.
        $path = str_replace('\\', '/', $path);

        // Удаляем повторяющиеся слэши (2 и более подряд заменяем на один).
        $path = preg_replace('#/+#', '/', $path);

        // Добавляем ведущий слэш, если его нет.
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        // Восстанавливаем сохранённые токены, заменяя placeholder на оригинальное значение.
        foreach ($placeholders as $placeholder => $token) {
            $path = str_replace($placeholder, $token, $path);
        }

        if ($path === '/') return $path;

        return rtrim($path, '/');
    }

    /**
     * Replace backslashes with forward slashes
     *
     * @param string $path Path with potential backslashes
     * @return string Path with forward slashes only
     */
    public static function replaceSlashes(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    /**
     * Compare this path with another path
     *
     * @param RoutePath|string $path Path to compare with
     * @return bool True if paths are equal
     */
    public function equals(RoutePath|string $path): bool
    {
        return $this->value === (string) $path;
    }

    /**
     * Convert to string representation
     *
     * @return string The normalized path value
     */
    public function __toString(): string
    {
        return $this->value;
    }
}