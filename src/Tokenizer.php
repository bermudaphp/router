<?php

namespace Bermuda\Router;

/**
 * @internal
 */
final class Tokenizer
{
    private array $limiters;

    public function __construct(
        string $leftLimiter = '[',
        string $rightLimiter = ']'
    ) {
        $this->limiters = [$leftLimiter, $rightLimiter];
    }

    public function isToken(string $pathSegment): bool
    {
        if (empty($pathSegment)) return false;
        return $pathSegment[0] === $this->limiters[0]
            && $pathSegment[mb_strlen($pathSegment)-1] === $this->limiters[1];
    }

    public function isRequired(string $token): bool
    {
        return $token[1] !== '?';
    }

    public function hasTokens(string $routePath): bool
    {
        $segments = explode('/', $routePath);
        foreach ($segments as $segment) {
            if (empty($segment)) continue;
            if ($this->isToken($segment)) return true;
        }

        return false;
    }

    public function setTokens(string $routePath, array $tokens): string
    {
        $path = '';
        foreach (explode('/', $routePath) as $segment) {
            if (empty($segment)) continue;
            if ($this->isToken($segment)) {
                list($id) = $this->parseToken($segment);
                if (isset($tokens[$id])) {
                    $segment = sprintf("[%s$id:$tokens[$id]]", !$this->isRequired($segment) ? '?' : '');
                }
            }
            $path .= "/$segment";
        }

        return $path;
    }

    public function parseToken(string $token): array
    {
        $token = trim($token, implode('', $this->limiters));
        $token = ltrim($token, '?');
        if (str_contains($token, ':')) return explode(':', $token, 2);

        return [$token, null];
    }
}
