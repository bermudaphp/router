<?php

namespace Bermuda\Router;

use RuntimeException;
use InvalidArgumentException;
use Bermuda\Router\Exception\GeneratorException;
use Bermuda\Router\Exception\RouteNotFoundException;
use Bermuda\Router\Exception\MethodNotAllowedException;
use function Bermuda\String\str_contains_all;

class Routes implements RouteMap, Matcher, Generator
{
    protected array $map = [];
    protected array $routes = [
        'static' => [],
        'dynamic' => []
    ];

    /**
     * @return \Generator<Route>
     */
    public function getIterator(): \Generator
    {
        foreach (array_merge($this->routes['static'], $this->routes['dynamic']) as $name => $data) {
            yield $name => Route::fromArray($data);
        }
    }

    /**
     * @return Route[]
     */
    public function toArray(): array
    {
        return iterator_to_array($this);
    }

    /**
     * @inheritDoc
     */
    public function group(string $prefix, mixed $middleware = null, ?array $tokens = null, callable $callback = null): RouteMap
    {
        if ($callback === null) {
            throw new InvalidArgumentException('The argument [ callback ] cannot be null');
        }

        $callback($map = new class($prefix, $middleware, $tokens) extends Routes {
            public function __construct(private string $prefix, private mixed $middleware, private ?array $tokens = null)
            {
            }

            protected function add(string       $name, string $path, $handler,
                                   array|string $methods = null, ?array $tokens = null,
                                   mixed        $middleware = null): self
            {

                if ($this->tokens !== null) {
                    if ($tokens !== null) {
                        $tokens = array_merge($this->tokens, $tokens);
                    } else {
                        $tokens = $this->tokens;
                    }
                }

                if ($this->middleware !== null) {
                    if ($middleware === null) {
                        $middleware = $this->middleware;
                    } else {
                        if (!is_array($middleware)) {
                            $middleware = [$middleware];
                        }

                        if (is_array($this->middleware)) {
                            $middleware = array_merge($this->middleware, $middleware);
                        } else {
                            array_unshift($middleware, $this->middleware);
                        }
                    }
                }

                return parent::add($name, $this->prefix . $path, $handler, $methods, $tokens, $middleware);
            }
        });

        $this->routes['static'] = array_merge($this->routes['static'], $map->routes['static']);
        $this->routes['dynamic'] = array_merge($this->routes['dynamic'], $map->routes['dynamic']);

        foreach ($map->map as $path => $value) {
            if (isset($this->map[$path])) {
                $this->map[$path] = array_merge($this->map[$path], $value);
            } else {
                $this->map[$path] = $value;
            }
        }

        return $this;
    }

    protected function add(string       $name, string $path, $handler,
                           array|string $methods, ?array $tokens = null,
                           mixed        $middleware = null): self
    {
        if (true === ($needConvertToArray = is_string($methods)) && str_contains($methods, '|')) {
            $methods = explode('|', $methods);
        } elseif ($needConvertToArray) {
            $methods = [$methods];
        }

        $methods = array_map('strtoupper', $methods);

        $data = [
            'name' => $name,
            'path' => $path,
            'handler' => $handler,
            'methods' => $methods
        ];

        if ($middleware != null) {
            $data['middleware'] = $middleware;
        }

        if ($tokens != null) {
            $data['tokens'] = $tokens;
        }

        if (str_contains_all($path, ['{', '}'])) {
            $this->routes['dynamic'][$name] = $data;
        } else {
            $this->routes['static'][$name] = $data;
            if (isset($this->map[$path])) {
                $this->map[$path][] = $name;
            } else {
                $this->map[$path] = [$name];
            }
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function get(string $name, string $path,
                               $handler, ?array $tokens = null,
                        mixed  $middleware = null): RouteMap
    {
        return $this->add($name, $path, $handler, 'GET', $tokens, $middleware);
    }

    /**
     * @inheritDoc
     */
    public function post(
        string $name,
        string $path,
               $handler,
        ?array $tokens = null,
        mixed  $middleware = null): RouteMap
    {
        return $this->add($name, $path, $handler, 'POST', $tokens, $middleware);
    }

    /**
     * @inheritDoc
     */
    public function delete(
        string $name,
        string $path,
               $handler,
        ?array $tokens = null,
        mixed  $middleware = null): RouteMap
    {
        return $this->add($name, $path, $handler, 'DELETE', $tokens, $middleware);
    }

    /**
     * @inheritDoc
     */
    public function put(
        string $name,
        string $path,
               $handler,
        ?array $tokens = null,
        mixed  $middleware = null): RouteMap
    {
        return $this->add($name, $path, $handler, 'PUT', $tokens, $middleware);
    }

    /**
     * @inheritDoc
     */
    public function patch(
        string $name,
        string $path,
               $handler,
        ?array $tokens = null,
        mixed  $middleware = null): RouteMap
    {
        return $this->add($name, $path, $handler, 'PATCH', $tokens, $middleware);
    }

    /**
     * @inheritDoc
     */
    public function options(
        string $name,
        string $path,
               $handler,
        ?array $tokens = null,
        mixed  $middleware = null): RouteMap
    {
        return $this->add($name, $path, $handler, 'OPTIONS', $tokens, $middleware);
    }

    /**
     * @inheritDoc
     */
    public function any(
        string       $name,
        string       $path,
                     $handler,
        array|string $methods = null,
        ?array       $tokens = null,
        mixed        $middleware = null): RouteMap
    {
        return $this->add($name, $path, $handler, $methods ?? Route::$requestMethods, $tokens, $middleware);
    }

    /**
     * @inheritDoc
     */
    public function generate(RouteMap $routes, string $name, array $attributes = []): string
    {
        if ($routes instanceof self) {
            $route = $this->routes['static'][$name]
                ?? ($this->routes['dynamic'][$name] ?? null);
            if ($route === null) {
                throw RouteNotFoundException::forName($name);
            }
        } else {
            $route = $routes->get($name);
        }

        $path = '';
        $segments = explode('/', $route['path']);

        foreach ($segments as $segment) {
            if (!empty($segment)) {
                if (Attribute::is($segment)) {
                    $id = Attribute::trim($segment);
                    if (!Attribute::isOptional($segment)) {
                        if (!isset($attributes[$id])) {
                            throw GeneratorException::create($id, $route['name']);
                        }
                    }
                    if (!empty($attribute = $attributes[$id] ?? '')) {
                        $path .= '/' . $attribute;
                    }

                    continue;
                }

                $path .= '/' . $segment;
            }
        }

        return $path;
    }

    /**
     * @inheritDoc
     */
    public function match(RouteMap $map, string $requestMethod, string $uri): Route
    {
        $method = strtoupper($requestMethod);
        $path = rawurldecode(parse_url($uri, PHP_URL_PATH));
        $path == '/' ?: $path = rtrim($path, '/');

        if ($map instanceof self) {
            if (isset($map->map[$path])) {
                foreach ($map->map[$path] as $name) {
                    if (in_array($method, $map->routes['static'][$name]['methods'])) {
                        return Route::fromArray($map->routes['static'][$name]);
                    }

                    ($e = MethodNotAllowedException::make($path, $requestMethod))
                        ->addAllowedMethods($map->routes['static'][$name]['methods']);
                }
            }

            $routes = $map->routes['dynamic'];
        }

        foreach ($routes ?? $map as $route) {
            if (preg_match($route['regexp'] ?? $this->buildRegexp($route), $path) === 1) {
                if (in_array($method, $route['methods'])) {
                    return $this->parseAttributes($route, $path);
                }

                ($e ?? $e = MethodNotAllowedException::make($path, $requestMethod))
                    ->addAllowedMethods($route['methods']);
            }
        }

        throw $e ?? RouteNotFoundException::forPath($path);
    }

    /**
     * @param array $routeData
     * @return string
     */
    private function buildRegexp(Route|array $routeData): string
    {
        if (empty($path = $routeData['path']) || $path == '/') {
            return '#^/$#';
        }

        $pattern = '#^';
        $segments = explode('/', $path);

        foreach ($segments as $segment) {
            if (!empty($segment)) {
                if (Attribute::is($segment)) {
                    $id = Attribute::trim($segment);
                    $pattern .= Attribute::isOptional($segment)
                        ? '(/('.(($routeData['tokens'][$id] ?? Route::$tokens[$id] ?? '.+')).'))?'
                        : '/('.(($routeData['tokens'][$id] ?? Route::$tokens[$id] ?? '.+')).')';
                } else {
                    $pattern .= '/'.$segment;
                }
            }
        }

        return $pattern . '/?$#';
    }

    private function parseAttributes(Route|array $route, string $path): Route
    {
        $paths = explode('/', $path);
        $segments = explode('/', $route['path']);

        $attributes = [];
        foreach ($segments as $i => $segment) {
            if ($segment != ($paths[$i] ?? '')) {
                $attributes[$s = Attribute::trim($segment)] = $paths[$i] ?? null;
            }
        }
        
        if (isset($segments[$i])) {
            $attributes[$s] = $attributes[$s]
                . '/' . implode('/', array_slice($paths, $i + 1));
        }

        if (is_array($route)) {
            $route['attributes'] = $attributes;
            return Route::fromArray($route);
        }

        return $route->withAttributes($attributes);
    }

    /**
     * @inheritDoc
     */
    public function resource(Resource|string $resource): RouteMap
    {
        if (!is_subclass_of($resource, Resource::class)) {
            throw new RuntimeException(sprintf('Resource must be subclass of %s', Resource::class));
        }

        return $resource::register($this);
    }

    /**
     * @inheritDoc
     */
    public function route(string $name): Route
    {
        $route = $this->routes['static'][$name]
            ?? ($this->routes['dynamic'][$name] ?? null);

        if ($route) {
            return Route::fromArray($route);
        }

        throw RouteNotFoundException::forName($name);
    }
}
