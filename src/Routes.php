<?php

namespace Bermuda\Router;

use Bermuda\Router\Exception\GeneratorException;
use Bermuda\Router\Exception\MethodNotAllowedException;
use Bermuda\Router\Exception\RouteNotFoundException;
use RuntimeException;

class Routes implements RouteMap, Matcher, Generator
{
    use AttributeNormalizer;

    protected array $routes = [];

    /**
     * @return Route[]
     */
    public function getIterator(): \Generator
    {
        foreach ($this->routes as $name => $route) {
            yield $name => Route::fromArray($route);
        }
    }

    /**
     * @return Route[]
     */
    public function toArray(): array
    {
        return $this->routes;
    }

    /**
     * @inheritDoc
     */
    public function group(string $prefix, mixed $middleware = null, ?array $tokens = null, callable $callback = null): RouteMap
    {
        if ($callback === null) {
            throw new \InvalidArgumentException('The argument [ callback ] cannot be null');
        }

        $callback($map = new class($prefix, $middleware, $tokens) extends Routes {
            public function __construct(private string $prefix, private mixed $middleware, private ?array $tokens = null)
            {
            }

            protected function add(string $name, string $path, $handler,
                                   array|string $methods = null, ?array $tokens = null,
                                   mixed $middleware = null): self
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

                        $middleware = array_merge($this->middleware, $middleware);
                    }
                }

                $path = $this->prefix . $path;

                return parent::add($name, $path, $handler, $methods, $tokens, $middleware);
            }
        });

        foreach ($map->routes as $name => $routeData) {
            $this->routes[$name] = $routeData;
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function get(string $name, string $path,
        $handler, ?array $tokens = null,
        mixed $middleware = null): RouteMap
    {
        return $this->add($name, $path, $handler, 'GET', $tokens, $middleware);
    }

    protected function add(string $name, string $path, $handler,
                           array|string $methods, ?array $tokens = null,
                           mixed $middleware = null): self
    {
        if (true === ($needConvertToArray = is_string($methods)) && str_contains($methods, '|')) {
            $methods = explode('|', $methods);
        } elseif ($needConvertToArray) {
            $methods = [$methods];
        }

        if ($tokens === null) {
            $tokens = Route::$routeTokens;
        } else {
            $tokens = array_merge(Route::$routeTokens, $tokens);
        }

        $methods = array_map('strtoupper', $methods);

        $this->routes[$name] = compact('name', 'path', 'handler', 'methods', 'tokens', 'middleware');

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function post(
        string $name,
        string $path,
        $handler,
        ?array $tokens = null,
        mixed $middleware = null): RouteMap
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
        mixed $middleware = null): RouteMap
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
        mixed $middleware = null): RouteMap
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
        mixed $middleware = null): RouteMap
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
        mixed $middleware = null): RouteMap
    {
        return $this->add($name, $path, $handler, 'OPTIONS', $tokens, $middleware);
    }

    /**
     * @inheritDoc
     */
    public function any(
        string $name,
        string $path,
        $handler,
        array|string $methods = null,
        ?array $tokens = null,
        mixed $middleware = null): RouteMap
    {
        return $this->add($name, $path, $handler, $methods ?? Route::$requestMethods, $tokens, $middleware);
    }

    /**
     * @inheritDoc
     */
    public function generate(RouteMap $routes, string $name, array $attributes = []): string
    {
        if ($routes instanceof self) {
            if (!isset($this->routesData[$name])) {
                throw RouteNotFoundException::forName($name);
            }

            $route = $this->routesData[$name];
        } else {
            $route = $routes->get($name);
        }

        $segments = explode('/', $route['path']);

        $path = '';

        foreach ($segments as $segment) {
            if (empty($segment)) {
                continue;
            }

            $path .= '/';

            if ($this->isAttribute($segment)) {
                $attribute = $this->normalize($segment);

                if (!$this->isOptional($segment)) {
                    if (!array_key_exists($attribute, $attributes)) {
                        return new GeneratorException('Missing route attribute with name: ' . $attribute);
                    }

                    $path .= $attributes[$attribute];
                } elseif (array_key_exists($attribute, $attributes)) {
                    $path .= $attributes[$attribute];
                }
            } else {
                $path .= $segment;
            }
        }

        return empty($path) ? '/' : rtrim($path, '/');
    }

    /**
     * @inheritDoc
     */
    public function match(RouteMap $routes, string $requestMethod, string $uri): Route
    {
        if ($routes instanceof self) {
            $routes = $routes->routes;
        }

        $method = strtoupper($requestMethod);

        foreach ($routes as $route) {
            if (preg_match($this->buildRegexp($route), $path = $this->getPath($uri), $matches) === 1) {
                if (in_array($method, $route['methods'])) {
                    return $this->parseAttributes($route, $matches);
                }

                ($e ?? $e = MethodNotAllowedException::make($path, $requestMethod))
                    ->addAllowedMethods($route['methods']);
            }
        }

        throw $e ?? RouteNotFoundException::forPath($path ?? $this->getPath($uri));
    }

    /**
     * @inheritDoc
     */
    public function resource(string $resource): RouteMap
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
        $route = $this->routes[$name] ?? null;

        if ($route) {
            return Route::fromArray($route);
        }

        throw RouteNotFoundException::forName($name);
    }

    /**
     * @param array $routeData
     * @return string
     */
    private function buildRegexp(array|Route $routeData): string
    {
        if (($path = $routeData['path']) === '' || $path === '/') {
            return '#^/$#';
        }

        $pattern = '#^';

        $segments = explode('/', $path);

        foreach ($segments as $segment) {
            if (empty($segment)) {
                continue;
            }

            if ($this->isOptional($segment)) {
                $pattern .= '/??(';

                if ($this->isAttribute($segment)) {
                    $token = $this->normalize($segment);
                    $pattern .= $routeData['tokens'][$token] ?? '(.+)';
                } else {
                    $pattern .= $segment;
                }

                $pattern .= ')??';
                continue;
            }

            $pattern .= '/';

            if ($this->isAttribute($segment)) {
                $token = $this->normalize($segment);
                $pattern .= $routeData['tokens'][$token] ?? '(.+)';
            } else {
                $pattern .= $segment;
            }

        }

        return $pattern . '/?$#';
    }

    /**
     * @param string $uri
     * @return string
     */
    private function getPath(string $uri): string
    {
        return rawurldecode(parse_url($uri, PHP_URL_PATH));
    }

    private function parseAttributes(Route|array $route, array $matches): Route
    {
        if (count($matches) > 1) {
            array_shift($matches);
        } else {
            return is_array($route) ? Route::fromArray($route) : $route;
        }

        foreach (explode('/', $route['path']) as $segment) {
            if ($this->isAttribute($segment)) {
                $attributes[$this->normalize($segment)] = ltrim(array_shift($matches), '/');
            }
        }

        return is_array($route) ? Route::fromArray(array_merge($route, compact('attributes')))
            : $route->withAttributes($attributes);
    }
}
