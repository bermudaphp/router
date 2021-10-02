<?php

namespace Bermuda\Router;

use Bermuda\Arrayable;
use Fig\Http\Message\RequestMethodInterface;
use InvalidArgumentException;

final class Route implements Arrayable
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
    
    private string $name;
    private string $path;
    private array $handler;
    private array $tokens = [];
    private array $methods = [];
    private array $attributes = [];

    private function __construct(array $routeData)
    {
        $this->name = $routeData['name'];
        $this->path = $routeData['path'];
        $this->handler = [$routeData['handler']];

        $this->setTokens($routeData['tokens']);
        $this->setMethods($routeData['methods']);
        $this->setMiddleware($routeData['middleware'] ?? null);
    }

    private function setTokens(?array $tokens): self
    {
        $this->tokens = array_merge($this->tokens, (array)$tokens);
        return $this;
    }

    private function setMethods($methods): self
    {
        if (true === $needConvertToArray = (is_string($methods) && str_contains($methods, '|'))) {
            $methods = explode('|', $methods);
        } elseif ($needConvertToArray ?? false) {
            $methods = [$methods];
        }

        $this->methods = array_map('strtoupper', $methods);

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
            array_unshift($this->handler, $middleware);
        }
    }

    private function setAfterMiddleware($middleware): void
    {
        if ($middleware !== null) {
            array_push($this->handler, $middleware);
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

        if (!isset($data['methods'])) {
            $data['methods'] = self::$requestMethods;
        }

        if (!isset($data['tokens'])) {
            $data['tokens'] = self::$routeTokens;
        }

        return new self($data);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
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
        return count($this->handler) > 1 ? $this->handler : $this->handler[0];
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
            return $this->methods;
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
            return $this->tokens;
        }

        return (clone $this)->setTokens($tokens);
    }

    /**
     * @param $middleware
     * @return self
     */
    public function middleware($middleware): self
    {
        return (clone $this)->setMiddleware($middleware);
    }
}
