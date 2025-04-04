<?php

namespace Bermuda\Router;

use Psr\Container\ContainerInterface;

use function Bermuda\Config\conf;

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
        return strpos($routePath, $this->limiters[0]) !== false
            && strpos($routePath, $this->limiters[1]) !== false;
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

    public function splitPath(string $path): array
    {
        $path = trim($path, '/');
        $segments = [];

        $chars = str_split($path);
        $ignoreSlash = false;

        $i = 0;

        foreach ($chars as $pos => $char) {
            isset($segments[$i]) ?: $segments[$i] = '';
            if ($char === '/') {
                if ($ignoreSlash) {
                    $segments[$i] .= $char;
                    continue;
                } elseif ($chars[$pos+1] === $this->limiters[0]) $ignoreSlash = true;

                $i++;
                continue;
            } elseif ($char === $this->limiters[0]) {
                $ignoreSlash = true;
            } elseif ($char === $this->limiters[1]) {
                $ignoreSlash = false;
            }
            $segments[$i] .= $char;
        }

        return $segments;
    }

    public static function createFromContainer(ContainerInterface $container): Tokenizer
    {
        $config = conf($container);
        $limiters = $config->get(ConfigProvider::CONFIG_KEY_LIMITERS, []);

        return new self($limiters[0] ?? '[', $limiters[1] ?? ']');
    }
}
