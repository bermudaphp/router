<?php

namespace Bermuda\Router;

/**
 * Enumeration of HTTP methods with additional utility methods
 */
enum HttpMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case DELETE = 'DELETE';
    case PATCH = 'PATCH';
    case HEAD = 'HEAD';
    case OPTIONS = 'OPTIONS';
    case TRACE = 'TRACE';
    case CONNECT = 'CONNECT';

    public function isSafe(): bool
    {
        return match($this) {
            self::GET, self::HEAD, self::OPTIONS => true,
            default => false
        };
    }

    /**
     * @return HttpMethod[]
     */
    public static function safeMehods(): array
    {
        return [self::GET, self::HEAD, self::OPTIONS];
    }

    public static function normalize(string $method): string
    {
        return strtoupper($method);
    }

    public static function all(): array
    {
        return [
            'GET', 'POST', 'PUT',
            'DELETE', 'OPTIONS', 'HEAD',
            'PATCH', 'TRACE', 'CONNECT'
        ];
    }

    public static function create(string $method): self
    {
        return self::from(static::normalize($method));
    }
}
