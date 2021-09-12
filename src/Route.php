<?php

namespace Bermuda\Router;

use Bermuda\Arrayable;
use Fig\Http\Message\RequestMethodInterface;
use InvalidArgumentException;

class Route implements Arrayable
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

    protected array $tokens = [];
    protected array $methods = [];
    protected array $attributes = [];

    private function __construct(protected string $name,
                                 protected string $path, protected array $handler,
                                 array            $tokens = [], array $methods = [],
                                 ?array           $middleware = null
    )
    {
        $this->setTokens($tokens);
        $this->setMethods($methods);
        $this->setMiddleware($middleware);
    }

    private function setTokens(?array $tokens): self
    {
        $this->tokens = array_merge($this->tokens, (array)$tokens);
        return $this;
    }

    private function setMethods($methods): self
    {
        if (is_string($methods) && str_contains($methods, '|')) {
            $methods = explode('|', $methods);
        }

        $this->methods = array_map('strtoupper', (array)$methods);

        return $this;
    }

    private function setMiddleware($middleware): self
    {
        if ($middleware != null) {
            if ($before = !isset($middleware['before']) && $after = !isset($middleware['after'])) {
                $this->setBeforeMiddleware($middleware);
                return $this;
            }

            $after ?: $this->setAfterMiddleware($middleware['after']);
            $before ?: $this->setBeforeMiddleware($middleware['before']);
        }

        return $this;
    }

    private function setBeforeMiddleware($middleware): self
    {
        array_unshift($this->handler, $middleware);
        return $this;
    }

    private function setAfterMiddleware($middleware): self
    {
        array_push($this->handler, $middleware);
        return $this;
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

        return new self(
            $data['name'], $data['path'],
            $data['handler'],
            $data['tokens'] ?? self::$routeTokens,
            $data['methods'] ?? self::$requestMethods,
            $data['middleware'] ?? null
        );
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
    public function methods($methods = null): array|Route
    {
        if ($methods == null) {
            return $this->methods;
        }

        return (clone $this)->setMethods($methods);
    }

    /**
     * @return array|self
     */
    public function tokens(?array $tokens = null): array|Route
    {
        if ($tokens == null) {
            return $this->tokens;
        }

        return (clone $this)->setTokens($tokens);
    }

    /**
     * @return self
     */
    public function middleware($middleware): self
    {
        return (clone $this)->setMiddleware($middleware);
    }
}
