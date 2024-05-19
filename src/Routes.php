<?php

namespace Bermuda\Router;

use Bermuda\Router\Exception\RouterException;
use Bermuda\Router\Exception\GeneratorException;
use function Bermuda\VarExport\export_array;

class Routes implements RouteMap, Matcher, Generator
{
    use RouteCollector;

    /**
     * @var RouteGroup[]
     */
    private array $groups = [];

    public function __construct(
        protected readonly Tokenizer $tokenizer = new Tokenizer,
    ) {
    }

    public function match(RouteMap $routes, string $uri, string $requestMethod):? MatchedRoute
    {
        list($path, $requestMethod) = $this->preparePathAndMethod($uri, $requestMethod);

        if (!$routes instanceof Routes) {
            foreach ($routes as $route) {
                $result = $this->matchRoute($route, $path, $requestMethod);
                if ($result) return $result;
            }

            return null;
        }

        foreach ($routes->groups as $group) {
            if (str_starts_with($uri, $group->prefix)) {
                foreach ($group as $route) {
                    $result = $this->matchRoute($route, $path, $requestMethod);
                    if ($result) return $result;
                }
            }
        }

        foreach ($routes->routes as $route) {
            $result = $this->matchRoute($route, $path, $requestMethod);
            if ($result) return $result;
        }

        return null;
    }

    public function getRoute(string $name):? RouteRecord
    {
        foreach ($this->getIterator() as $route) {
            if ($route->name === $name) return $route;
        }

        return null;
    }

    /**
     * @throws GeneratorException
     */
    public function generate(RouteMap $routes, string $name, array $params = []): string
    {
        $route = $routes->getRoute($name);

        if (!$route) {
            throw new GeneratorException('Route "' . $name . '" not found');
        }

        $path = '';
        $segments = explode('/', $route->path);

        foreach ($segments as $segment) {
            if (!empty($segment)) {
                if ($this->tokenizer->isToken($segment)) {
                    list($id) = $this->tokenizer->parseToken($segment);
                    if ($this->tokenizer->isRequired($segment) && !isset($params[$id])) {
                        throw new GeneratorException('Missing required parameter "' . $id . '"');
                    }

                    if (!empty($param = $params[$id] ?? '')) {
                        $path .= "/$param";
                    }

                    continue;
                }


                $path .= '/' . $segment;
            }
        }

        return empty($path) ? '/' : $path;
    }

    /**
     * @return \Generator<RouteRecord>
     */
    public function getIterator(): \Generator
    {
        foreach ($this->groups as $group) {
            yield from $group;
        }

        foreach ($this->routes as $route) yield $route;
    }

    /**
     * @throws RouterException
     */
    public function group(string $name, ?string $prefix = null): RouteGroup
    {
        if (!$prefix) {
            if (!isset($this->groups[$name])) {
                throw new RouterException('Group "' . $name . '" not found');
            }

            return $this->groups[$name];
        }

        return $this->groups[$name] = new RouteGroup($name, $prefix);
    }

    /**
     * @throws ArrayExportException
     */
    public function cache(string $filename, callable $fileWriter = null): void
    {
        $routes = [];
        foreach ($this->getIterator() as $route) {
            $routeData = [
                'name' => $route->name,
                'path' => $this->tokenizer->setTokens($route->path, $route->tokens),
                'methods' => $route->methods,
                'handler' => $route->handler,
                'regexp' => $this->buildRegexp($route)
            ];

            if ($this->tokenizer->hasTokens($route->path)) $routes['dynamic'][] = $routeData;
            elseif (isset($routes['static'][$route->path])) {
                if (isset($routes['static'][$route->path]['path'])) {
                    $routes['static'][$route->path] = [$routes['static'][$route->path], $routeData];
                } else {
                    $routes['static'][$route->path][] = $routeData;
                }
            }
            else $routes['static'][$route->path] = $routeData;
        }

        $content = export_array($routes);

        if ($fileWriter) {
            $fileWriter($filename, $content);
            return;
        }

        file_put_contents($filename, '<?php ' . PHP_EOL .'return '. $content);
    }

    /**
     * @return static
     */
    public static function createFromCache(string $filename, array $context = null): RouteMap
    {
        return static::createFromArray((static function() use ($filename, $context): array {
            if ($context) extract($context);
            return require_once $filename;
        })());
    }

    private function buildRegexp(RouteRecord $route): string
    {
        if ($route->path === '' || $route->path === '/') {
            return '#^/$#';
        }

        $regexp = '#^';
        foreach (explode('/', $route->path) as $segment) {
            if (!empty($segment)) {
                if ($this->tokenizer->isToken($segment)) {
                    list($id, $pattern) = $this->tokenizer->parseToken($segment);
                    $pattern = $route->tokens[$id] ?? $pattern;
                    if (!$pattern) $pattern = '/('.$route->tokens[$id] ?? '.+'.')';
                    if (!$this->tokenizer->isRequired($segment)) $regexp .= "(/$pattern)?";
                    else $regexp .= '/'.$pattern;
                } else {
                    $regexp .= '/'.$segment;
                }
            }
        }

        return $regexp . '/?$#';
    }

    private function matchRoute(RouteRecord $route, string $path, string $requestMethod):? MatchedRoute
    {
        $pattern = $this->buildRegexp($route);
        if (preg_match($pattern, $path) === 1) {
            if (in_array($requestMethod, $route->methods)) {
                return $this->parseParams($route, $path);
            }
        }

        return null;
    }

    protected function preparePathAndMethod(string $uri, string $requestMethod): array
    {
        $path = rawurldecode(parse_url($uri, PHP_URL_PATH));
        return [$path == '/' ?: rtrim($path, '/'), strtoupper($requestMethod)];
    }

    private function parseParams(RouteRecord $route, string $path): MatchedRoute
    {
        $paths = explode('/', $path);
        $segments = explode('/', $route->path);

        $params = [];
        foreach ($segments as $i => $segment) {
            if ($segment != ($paths[$i] ?? '')) {
                list($id) = $this->tokenizer->parseToken($segment);
                $params[$id] = $paths[$i] ?? $route->defaults[$id] ?? null;
                if (is_numeric($params[$id])) $params[$id] = $params[$id] + 0;
            }
        }

        if (isset($i) && isset($paths[$i+1]) && isset($id)) {
            $params[$id] = $params[$id]
                . '/' . implode('/', array_slice($paths, $i + 1));
        }

        return new MatchedRoute(
            $route->name,
            $route->path,
            $route->handler,
            $route->methods,
            $params,
        );
    }

    public static function createFromArray(array $routes): static
    {
        return new class($routes) extends Routes
        {
            public function __construct(private readonly array $elements)
            {
                parent::__construct();
            }

            public function match(RouteMap $routes, string $uri, string $requestMethod): ?MatchedRoute
            {
                list($path, $requestMethod) = $this->preparePathAndMethod($uri, $requestMethod);

                if (!$routes instanceof $this) {
                    foreach ($routes as $route) {
                        $result = parent::match($route, $uri, $requestMethod);
                        if ($result) return $result;
                    }

                    return null;
                }

                if (isset($routes->elements['static'][$path])) {
                    if (isset($routes->elements['static'][$path]['path'])) {
                        if (in_array($requestMethod, $routes->elements['static'][$path]['methods'])) {
                            return MatchedRoute::fromArray($routes->elements['static'][$path]);
                        };
                    } else {
                        foreach ($routes->elements['static'][$path] as $routeData) {
                            if (in_array($requestMethod, $routeData['methods'])) {
                                return MatchedRoute::fromArray($routeData);
                            };
                        }
                    }
                }

                $parseParams = static function(array $route, string $path) use ($routes): MatchedRoute {
                    $paths = explode('/', $path);
                    $segments = explode('/', $route['path']);

                    $route['params'] = [];
                    foreach ($segments as $i => $segment) {
                        if ($segment != ($paths[$i] ?? '')) {
                            list($id) = $routes->tokenizer->parseToken($segment);
                            $route['params'][$id] = $paths[$i] ?? null;
                            if (is_numeric($route['params'][$id])) $route['params'][$id] = $route['params'][$id] + 0;
                        }
                    }

                    if (isset($i) && isset($paths[$i+1]) && isset($id)) {
                        $route['params'][$id] = $route['params'][$id]
                            . '/' . implode('/', array_slice($paths, $i + 1));
                    }

                    return MatchedRoute::fromArray($route);
                };

                foreach ($routes->elements['dynamic'] as $route) {
                    if (preg_match($route['regexp'], $path) === 1) {
                        if (in_array($requestMethod, $route['methods'])) {
                            return $parseParams($route, $path);
                        }
                    }
                }

                if (!empty($routes->routes)) return parent::match($routes, $uri, $requestMethod);
                return null;
            }
            public function getRoute(string $name): ?RouteRecord
            {
                foreach (array_merge($this->elements['static'], $this->elements['dynamic']) as $routeData) {
                    if ($routeData['name'] === $name) {
                        return RouteRecord::fromArray($routeData);
                    }
                }

                if (!empty($this->routes)) return parent::getRoute($name);
                return null;
            }

            /**
             * @return \Generator<RouteRecord>
             */
            public function getIterator(): \Generator
            {
                foreach (array_merge($this->elements['static'], $this->elements['dynamic']) as $routeData) {
                    yield RouteRecord::fromArray($routeData);
                }

                if ($this->routes !== []) yield from parent::getIterator();
            }
        };
    }
}
