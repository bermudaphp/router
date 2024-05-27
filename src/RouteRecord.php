<?php

namespace Bermuda\Router;

/**
 * @property-read string $name
 * @property-read string $path
 * @property-read mixed $handler
 * @property-read ?string $group
 * @property-read array<string> $tokens
 * @property-read array<string> $methods
 * @property-read ?array<string> $defaults
 * @property-read array<string> $params
 */
final class RouteRecord
{
    /**
     * @var array{
     *      handler: mixed,
     *      path: string,
     *      name: string,
     *      group: ?string,
     *      methods: array<string>,
     *      params: array<string, string>,
     *      tokens: array
     *  }
     */
    private array $routeData;

    public const id = '\d+';
    public const any = '.*';

    public function __construct(string $name, string $path, mixed $handler)
    {
        $this->routeData = [
            'name' => $name,
            'path' => normalize_path($path),
            'handler' => [$handler],
            'tokens' => ['id' => self::id, 'any' => self::any],
            'params' => [],
            'methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
            'group' => null,
            'defaults' => null
        ];
    }

    public function withToken(string $name, ?string $pattern): self
    {
        $route = clone $this;

        if (!$pattern) {
            unset($route->tokens[$name]);
            return $route;
        }
        
        $route->routeData['tokens'][$name] = $pattern;
        return $route;
    }

    public function __get(string $name)
    {
        if (isset($this->routeData[$name])) {
            if ($name === 'handler') {
                return \count($this->routeData['handler']) > 1 ?
                    $this->routeData['handler'] : $this->routeData['handler'][0];
            }
            
            return $this->routeData[$name];
        }

        return null;
    }

    public function withPrefix(string $prefix): self
    {
        $route = clone $this;
        $route->routeData['path'] = normalize_path("$prefix/{$route->routeData['path']}");

        return $route;
    }

    public function withName(string $name): self
    {
        $route = clone $this;
        $route->routeData['name'] = $name;

        return $route;
    }

    public function withDefaults(?array $defaults): self
    {
        $route = clone $this;
        $route->routeData['defaults'] = $defaults;

        return $route;
    }

    public function withParams(array $params): self
    {
        $route = clone $this;
        $route->routeData['params'] = $params;

        return $route;
    }

    public function withGroup(?string $name): self
    {
        $route = clone $this;
        $route->routeData['group'] = $name;

        return $route;
    }

    public function withMiddleware(?array $middleware): self
    {
        $route = clone $this;

        if (!$middleware) {
            $route->routeData['handler'] = array_pop($route->routeData['handler']);
            return $route;
        }
        
        $route->routeData['handler'] = [...$middleware, array_pop($route->routeData['handler'])];
        return $route;
    }
    
    public function addMiddleware(mixed $middleware): self
    {
        $route = clone $this;

        $handler = array_pop($route->routeData['handler']);

        $route->routeData['handler'][] = $middleware;
        $route->routeData['handler'][] = $handler;
        
        return $route;
    }

    public function withMethods(array $methods): self
    {
        $route = clone $this;
        $route->routeData['methods'] = array_map('strtoupper', $methods);

        return $route;
    }

    /**
     * @return array{
     *     handler: mixed,
     *     path: string,
     *     name: string,
     *     group: ?string,
     *     methods: array<string>,
     *     middleware: array<mixed>,
     *     tokens: array
     * }
     */
    public static function fromArray(array $routeData): self
    {
        $route = new self(
            $routeData['name'],
            $routeData['path'],
            $routeData['handler']
        );

        if (isset($routeData['methods'])) $route->routeData['methods'] = $routeData['methods'];
        if (isset($routeData['middleware'])) $route->routeData['handler'] = [...$routeData['middleware'], [$routeData['handler']]];
        if (isset($routeData['defaults'])) $route->routeData['defaults'] = $routeData['defaults'];
        if (isset($routeData['group'])) $route->routeData['group'] = $routeData['group'];
        if (isset($routeData['tokens'])) $route->routeData['tokens'] = $routeData['tokens'];

        return $route;
    }

    /**
     * @return array{
     *     handler: mixed,
     *     path: string,
     *     name: string,
     *     group: ?string,
     *     methods: array<string>,
     *     params: array<string, string>,
     *     tokens: array
     * }
     */
    public function toArray(): array
    {
        return $this->routeData;
    }

    public static function get(string $name, string $path, mixed $handler): self
    {
        return self::fromArray([
            'name' => $name,
            'path' => $path,
            'handler' => $handler,
            'methods' => ['GET'],
        ]);
    }

    public static function post(string $name, string $path, mixed $handler): self
    {
        return self::fromArray([
            'name' => $name,
            'path' => $path,
            'handler' => $handler,
            'methods' => ['POST'],
        ]);
    }

    public static function put(string $name, string $path, mixed $handler): self
    {
        return self::fromArray([
            'name' => $name,
            'path' => $path,
            'handler' => $handler,
            'methods' => ['PUT'],
        ]);
    }

    public static function patch(string $name, string $path, mixed $handler): self
    {
        return self::fromArray([
            'name' => $name,
            'path' => $path,
            'handler' => $handler,
            'methods' => ['PATCH'],
        ]);
    }

    public static function delete(string $name, string $path, mixed $handler): self
    {
        return self::fromArray([
            'name' => $name,
            'path' => $path,
            'handler' => $handler,
            'methods' => ['DELETE'],
        ]);
    }

    public static function head(string $name, string $path, mixed $handler): self
    {
        return self::fromArray([
            'name' => $name,
            'path' => $path,
            'handler' => $handler,
            'methods' => ['HEAD'],
        ]);
    }

    public static function options(string $name, string $path, mixed $handler): self
    {
        return self::fromArray([
            'name' => $name,
            'path' => $path,
            'handler' => $handler,
            'methods' => ['options'],
        ]);
    }
}
