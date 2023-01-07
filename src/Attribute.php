<?php

namespace Bermuda\Router;

final class Attribute
{
    private static $limiters = ['{', '}'];

    /**
     * @param string $left
     * @param string $right
     * @return void
     */
    public static function setLimiters(string $left, string $right): void
    {
        self::$limiters = [$left, $right];
    }

    /**
     * @return string[]
     */
    public static function getLimiters(): array
    {
        return self::$limiters;
    }

    /**
     * @param string $segment
     * @return string
     */
    public static function wrap(string $segment): string
    {
        return self::$limiters[0] . $segment . self::$limiters[1];
    }

    /**
     * @param string $segment
     * @return bool
     */
    public static function isOptional(string $segment): bool
    {
        return $segment[0] === '?';
    }

    /**
     * @param string $segment
     * @return bool
     */
    public static function is(string $segment): bool
    {
        if (empty($segment)) {
            return false;
        }

        return ($segment[0] == self::$limiters[0] || ($segment[0] == '?'
                    && $segment[1] == self::$limiters[0]))
            && $segment[mb_strlen($segment) - 1] == self::$limiters[1];
    }

    /**
     * @param string $placeholder
     * @return string
     */
    public static function trim(string $placeholder): string
    {
        return trim($placeholder, '?' . self::$limiters[0] . self::$limiters[1]);
    }
}
