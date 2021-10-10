<?php

namespace Bermuda\Router;

class Attribute
{
    /**
     * @param string $segment
     * @return bool
     */
    public function isOptional(string $segment): bool
    {
        return $segment[0] === '?';
    }

    /**
     * @param string $segment
     * @return bool
     */
    public function isAttribute(string $segment): bool
    {
        if (empty($segment)) {
            return false;
        }

        return ($segment[0] === '{' || ($segment[0] === '?' && $segment[1] === '{')) && $segment[mb_strlen($segment) - 1] === '}';
    }

    /**
     * @param string $placeholder
     * @return string
     */
    public function trim(string $placeholder): string
    {
        return trim($placeholder, '?{}');
    }
}
