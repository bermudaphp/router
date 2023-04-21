<?php

namespace Bermuda\Router;

use RuntimeException;
use InvalidArgumentException;
use Bermuda\Router\Exception\RouterException;
use Bermuda\Router\Exception\GeneratorException;
use Bermuda\Router\Exception\RouteNotFoundException;
use Bermuda\Router\Exception\MethodNotAllowedException;

use function Bermuda\VarExport\export_array;

class Routes implements RouteMap, Matcher, Generator, Cacheable
{
    protected array $routes = [
        'static' => [],
        'dynamic' => [],
        'map' => [],
    ];

    public static function createFromCache(string $filename, array $context = null): RouteMap
    {
        $routes = (static function() use ($filename, $context): array {
            if ($context) extract($context);
            return require_once $filename;
        })();

        $self = new static;
        $self->routes = $routes;

        return $self;
    }

    public function cache(string $filename, callable $fileWriter = null): void
    {
        if (empty($this->routes['static']) && empty($this->routes['dynamic'])) {
            throw new \LogicException('RouteMap is empty');
        }

        foreach ($this->routes() as $n => $route) {
            if (!isset($route['regexp'])) {
                $route['regexp'] = $this->buildRegexp($route);
            }

            isset($this->routes['static'][$n]) ?
                $this->routes['static'][$n] = $route
                : $this->routes['dynamic'][$n] = $route ;
        }

        $content = export_array($this->routes);

        if ($fileWriter) {
            $fileWriter($filename, $content);
            return;
        }

        file_put_contents($filename, '<?php ' . PHP_EOL .'return '. $content);
    }

    /**
     * @return \Generator<Route>
     */
    public function getIterator(): \Generator
    {
        foreach ($this->routes() as $n => $d) yield $n => Route::fromArray($d);
    }

    private function routes(): array
    {
        return array_merge($this->routes['static'], $this->routes['dynamic']);
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

        $callback($map = new class($this, $prefix, $middleware, $tokens) extends Routes {
            public function __construct(
                private Routes $wrapped,
                private string $prefix,
                private mixed $middleware,
                private ?array $tokens = null
            ) {
            }

            protected function add(string $name, string|Path $path, mixed $handler,
                                   array|string $methods = null, mixed $middleware = null): self
            {

                if ($path instanceof Path ) {
                    if ($this->tokens !== null) {
                        $path->mergeTokens($this->tokens);
                    }

                    $path->addPrefix($this->prefix);
                } else {
                    if ($this->tokens !== null) {
                        $path = new Path($path, $this->tokens);
                        $path->mergeTokens($this->tokens);
                        $path->addPrefix($this->prefix);
                    } else {
                        $path = $this->prefix . $path;
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

                $this->wrapped->add($name, $path, $handler, $methods, $middleware);
                return $this;
            }
        });

        return $this;
    }

    protected function add(string $name, string|Path $path, mixed $handler,
        array|string $methods, mixed $middleware = null): self
    {

        if (isset($this->routes['static'][$name]) || isset($this->routes['dynamic'][$name])) {
            throw new RouterException("Route with name [$name] alredy exists");
        }

        if (true === ($needConvertToArray = is_string($methods)) && str_contains($methods, '|')) {
            $methods = explode('|', $methods);
        } elseif ($needConvertToArray) {
            $methods = [$methods];
        }

        $methods = array_map('strtoupper', $methods);

        $data = [
            'name' => $name,
            'path' => (string) $path,
            'handler' => $handler,
            'methods' => $methods,
        ];

        if ($middleware != null) {
            $data['middleware'] = $middleware;
        }

        if ($path instanceof Path) {
            $data['tokens'] = $path->getTokens();
            $path = (string) $path;
        }

        list($left, $right) = Attribute::getLimiters();

        if (str_contains($path, $left) && str_contains($path, $right)) {
            $this->routes['dynamic'][$name] = $data;
        } else {
            $this->routes['static'][$name] = $data;
            if (isset($this->routes['map'][$path])) {
                $this->routes['map'][$path][] = $name;
            } else {
                $this->routes['map'][$path] = [$name];
            }
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function get(string $name, string|Path $path,
        mixed $handler, mixed  $middleware = null): RouteMap
    {
        return $this->add($name, $path, $handler, 'GET', $middleware);
    }

    /**
     * @inheritDoc
     */
    public function post(string $name, string|Path $path,
        mixed $handler, mixed  $middleware = null): RouteMap
    {
        return $this->add($name, $path, $handler, 'POST', $middleware);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $name, string|Path $path,
        mixed $handler, mixed  $middleware = null): RouteMap
    {
        return $this->add($name, $path, $handler, 'DELETE', $middleware);
    }

    /**
     * @inheritDoc
     */
    public function put(string $name, string|Path $path,
        mixed $handler, mixed  $middleware = null): RouteMap
    {
        return $this->add($name, $path, $handler, 'PUT', $middleware);
    }

    /**
     * @inheritDoc
     */
    public function patch(string $name, string|Path $path,
        mixed $handler, mixed  $middleware = null): RouteMap
    {
        return $this->add($name, $path, $handler, 'PATCH', $middleware);
    }

    /**
     * @inheritDoc
     */
    public function options(string $name, string|Path $path,
        mixed $handler, mixed  $middleware = null): RouteMap
    {
        return $this->add($name, $path, $handler, 'OPTIONS', $middleware);
    }

    /**
     * @inheritDoc
     */
    public function any(string $name, string|Path $path,
        mixed $handler, array|string $methods = null,
        mixed $middleware = null): RouteMap
    {
        return $this->add($name, $path, $handler, $methods ?? Route::$requestMethods, $middleware);
    }

    /**
     * @inheritDoc
     */
    public function generate(RouteMap $routes, string $name, array $attributes = []): string
    {
        $route = $routes->route($name);

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

        return empty($path) ? '/' : $path;
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
            if (isset($map->routes['map'][$path])) {
                foreach ($map->routes['map'][$path] as $name) {
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
        
        if (isset($paths[$i+1])) {
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
