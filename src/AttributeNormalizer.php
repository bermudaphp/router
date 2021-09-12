<?php

namespace Bermuda\Router;

trait AttributeNormalizer
{
    /**
     * @param string $segment
     * @return bool
     */
    private function isOptional(string $segment): bool
    {
        return str_contains($segment, '?');
    }

    /**
     * @param string $segment
     * @return bool
     */
    private function isAttribute(string $segment): bool
    {
        if (empty($segment)) {
            return false;
        }

        return ($segment[0] === '{' || $segment[0] === '?') && $segment[strlen($segment) - 1] === '}';
    }

    /**
     * @param string $placeholder
     * @return string
     */
    private function normalize(string $placeholder): string
    {
        return trim($placeholder, '?{}');
    }
}
