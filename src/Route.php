<?php

namespace Bermuda\Router;

use Bermuda\Arrayable;
use Fig\Http\Message\RequestMethodInterface;
use InvalidArgumentException;

final class Route implements Arrayable, \ArrayAccess
{
    public static array $requestMethods = [
        RequestMethodInterface::METHOD_GET,
        RequestMethodInterface::METHOD_POST,
        RequestMethodInterface::METHOD_PUT,
        RequestMethodInterface::METHOD_PATCH,
        RequestMethodInterface::METHOD_DELETE,
        RequestMethodInterface::METHOD_OPTIONS,
    ];

    public static array $routeTokens = [
        'id' => '\d+',
        'action' => '(create|read|update|delete)',
        'any' => '.*'
    ];

    private function __construct(private array $routeData)
    {
    }

    private function setTokens(?array $tokens): self
    {
        $this->routeData['tokens'] = array_merge($this->routeData['tokens'] ?? [], (array)$tokens);
        return $this;
    }

    private function setMethods($methods): self
    {
        if (true === ($needConvertToArray = is_string($methods)) && str_contains($methods, '|')) {
            $methods = explode('|', $methods);
        } elseif ($needConvertToArray) {
            $methods = [$methods];
        }
        
        $this->routeData['methods'] = array_map('strtoupper', $methods);

        return $this;
    }

    private function setMiddleware($middleware): self
    {
        if ($middleware !== null) {
            if (!isset($middleware['before']) && !isset($middleware['after'])) {
                $this->setBeforeMiddleware($middleware);
                return $this;
            }

            $this->setAfterMiddleware($middleware['after'] ?? null);
            $this->setBeforeMiddleware($middleware['before'] ?? null);
        }

        return $this;
    }

    private function setBeforeMiddleware($middleware): void
    {
        if ($middleware !== null) {
            array_unshift($this->routeData['handler'], $middleware);
        }
    }

    private function setAfterMiddleware($middleware): void
    {
        if ($middleware !== null) {
            array_push($this->routeData['handler'], $middleware);
        }
    }

    /**
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        foreach (['name', 'path', 'handler'] as $key) {
            if (!array_key_exists($key, $data)) {
                throw new InvalidArgumentException(sprintf('Missing %s $data[\'%s\']', __METHOD__, $key));
            }
        }

        $route = new self([
            'name' => $data['name'],
            'path' => $data['path'],
            'handler' => [$data['handler']],
        ]);

        if ((is_array($data['methods'] ?? null)
                || is_string($data['methods'] ?? null))
            && !empty($data['methods'])) {
            $route->setMethods($data['methods']);
        } else {
            $route->setMethods(self::$requestMethods);
        }

        if (!is_array($data['tokens'] ?? null)) {
            $route->setTokens(self::$requestMethods);
        } elseif ($data['tokens'] !== []) {
            $route->setTokens($data['tokens']);
        }

        if (is_array($data['attributes'] ?? null) && $data['attributes'] !== []) {
            $route->routeData['attributes'] = $data['attributes'];
        }

        if (isset($data['middleware'])) {
            $route->setMiddleware($data['middleware'] ?? null);
        }

        return $route;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->routeData['name'];
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->routeData['path'];
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->routeData['attributes'] ?? [];
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->routeData;
    }

    /**
     * @param array $attributes
     * @return self
     */
    public function withAttributes(array $attributes): self
    {
        $route = clone $this;
        $route->attributes = $attributes;

        return $route;
    }

    /**
     * @return mixed
     */
    public function getHandler(): mixed
    {
        return count($handler = $this->routeData['handler']) > 1
            ? $handler : $handler[0];
    }

    /**
     * @param string $prefix
     * @return self
     */
    public function withPrefix(string $prefix): self
    {
        $route = clone $this;
        $route->path = $prefix . $this->path;

        return $route;
    }

    /**
     * @param array|string|null $methods
     * @return array|self
     */
    public function methods(array|string|null $methods = null): array|self
    {
        if ($methods === null) {
            return $this->routeData['methods'];
        }

        return (clone $this)->setMethods($methods);
    }

    /**
     * @param array|null $tokens
     * @return array|self
     */
    public function tokens(?array $tokens = null): array|self
    {
        if ($tokens === null) {
            return $this->routeData['tokens'];
        }

        return (clone $this)->setTokens($tokens);
    }

    /**
     * @param $middleware
     * @return self
     */
    public function middleware($middleware): self
    {
        if ($middleware === null) {
            return $this;
        }

        return (clone $this)->setMiddleware($middleware);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->routeData[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->routeData[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        throw new \RuntimeException('Route is not mmutable');
    }

    public function offsetUnset($offset): void
    {
        throw new \RuntimeException('Route is not muttable');
    }
}
