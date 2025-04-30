<?php

namespace Bermuda\Router;

use Bermuda\Router\Exception\RouterException;
use Bermuda\Router\Exception\GeneratorException;
use Psr\Container\ContainerInterface;

use function Bermuda\VarExport\export_array;

class Routes implements RouteMap, Matcher, Generator
{
    use RouteCollector;

    /**
     * @var RouteGroup[]
     */
    protected array $groups = [];

    public function __construct(
        protected readonly Tokenizer $tokenizer = new Tokenizer,
    ) {
    }

    public function __clone(): void
    {
        $groups = $this->groups;
        $routes = $this->routes;
        
        $this->routes = [];
        $this->groups = [];

        foreach ($routes as $k => $route) $this->routes[$k] = clone $route;
        foreach ($groups as $k => $group) $this->groups[$k] = $group::copy($this, $group);
    }

    public function match(RouteMap $routes, string $uri, string $requestMethod):? RouteRecord
    {
        if ($routes instanceof Matcher && $routes !== $this) {
            $route = $routes->match($routes, $uri, $requestMethod);
            if ($route) return $route;
        }

        $path = $this->extractPath($uri);
        $requestMethod = $this->normalizeRequestMethod($requestMethod);

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
        $segments = $this->tokenizer->splitPath($route->path);

        foreach ($segments as $segment) {
            if (!empty($segment)) {
                if ($this->tokenizer->isToken($segment)) {
                    list($id) = $this->tokenizer->parseToken($segment);
                    if ($this->tokenizer->isRequired($segment) && !isset($params[$id])) {
                        throw new GeneratorException('Missing required parameter "' . $id . '"');
                    }

                    if (!empty($param = $params[$id] ?? '')) {
                        $param = rawurlencode($param);
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

        return $this->groups[$name] = new RouteGroup($name, $prefix, $this);
    }

    /**
     * @throws ArrayExportException
     */
    public function cache(string $filename, ?callable $fileWriter = null): void
    {
        $routes = [];
        foreach ($this->getIterator() as $route) {
            $routeData = [
                'name' => $route->name,
                'path' => $this->tokenizer->setTokens($route->path, $route->tokens),
                'methods' => $route->methods,
                'handler' => $route->handler,
                'regexp' => $this->compileRouteRegex($route),
                'defaults' => $route->defaults
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
    public static function createFromCache(string $filename, ?array $context = null): RouteMap
    {
        return static::createFromArray((static function() use ($filename, $context): array {
            if ($context) extract($context);
            return require_once $filename;
        })());
    }

    private function compileRouteRegex(RouteRecord $route): string
    {
        if ($route->path === '' || $route->path === '/') {
            return '#^/$#';
        }

        $regexp = '#^';
        foreach ($this->tokenizer->splitPath($route->path) as $segment) {
            if (!empty($segment)) {
                if ($this->tokenizer->isToken($segment)) {
                    list($id, $pattern) = $this->tokenizer->parseToken($segment);
                    $pattern = $route->tokens[$id] ?? $pattern;
                    if (!$pattern) $pattern = $route->tokens[$id] ?? '.+';
                    if (!$this->tokenizer->isRequired($segment)) $regexp .= "(/$pattern)?";
                    else $regexp .= '/'.$pattern;
                } else {
                    $regexp .= '/'.$segment;
                }
            }
        }

        return $regexp . '/?$#';
    }

    private function matchRoute(RouteRecord $route, string $path, string $requestMethod):? RouteRecord
    {
        $pattern = $this->compileRouteRegex($route);
        if (preg_match($pattern, $path) === 1) {
            if (in_array($requestMethod, $route->methods)) {
                return $route->withParams($this->extractRouteParameters($route, $path));
            }
        }

        return null;
    }

    protected function extractPath(string $uri): string 
    {
        return ($path = rawurldecode(parse_url($uri, PHP_URL_PATH))) == '/' ?: rtrim($path, '/');
    }

    protected function normalizeRequestMethod(string $uri, string $requestMethod): string
    {
        return strtoupper($requestMethod);
    }

    private function extractRouteParameters(RouteRecord $route, string $path): array
    {
        $paths = explode('/', $path);
        $segments = $this->tokenizer->splitPath($route->path);

        $params = [];
        foreach ($segments as $i => $segment) {
            if ($segment != ($paths[$i] ?? '')) {
                list($id) = $this->tokenizer->parseToken($segment);
                if (empty($paths[$i])) $params[$id] = $route->defaults[$id] ?? null;
                else $params[$id] = $paths[$i];
                if (is_numeric($params[$id])) $params[$id] = $params[$id] + 0;
            }
        }

        if (isset($i) && isset($paths[$i+1]) && isset($id)) {
            $params[$id] = $params[$id]
                . '/' . implode('/', array_slice($paths, $i + 1));
        }

        return $params;
    }

    public static function createFromArray(array $routes): static
    {
        return new class($routes) extends Routes
        {
            private array $staticRoutes;
            private array $dynamicRoutes;
            
            public function __construct(array $elements)
            {
                parent::__construct();
                $this->staticRoutes = $elements['static'] ?? [];
                $this->dynamicRoutes = $elements['dynamic'] ?? [];
            }

            public function match(RouteMap $routes, string $uri, string $requestMethod):? RouteRecord
            {
                list($path, $requestMethod) = $this->preparePathAndMethod($uri, $requestMethod);

                if (!$routes instanceof $this) {
                    foreach ($routes as $route) {
                        $result = parent::match($route, $uri, $requestMethod);
                        if ($result) return $result;
                    }

                    return null;
                }

                if (isset($routes->staticRoutes[$path])) {
                    if (isset($routes->staticRoutes[$path]['path'])) {
                        if (in_array($requestMethod, $routes->staticRoutes[$path]['methods'])) {
                            return RouteRecord::fromArray($routes->staticRoutes[$path]);
                        };
                    } else {
                        foreach ($routes->staticRoutes[$path] as $routeData) {
                            if (in_array($requestMethod, $routeData['methods'])) {
                                return RouteRecord::fromArray($routeData);
                            };
                        }
                    }
                }

                $extractRouteParameters = static function(array $route, string $path) use ($routes): RouteRecord {
                    $paths = explode('/', $path);
                    if (empty($paths[0])) array_shift($paths);
                    $segments = $routes->tokenizer->splitPath($route['path']);

                    $route['params'] = [];
                    foreach ($segments as $i => $segment) {
                        if ($segment != ($paths[$i] ?? '')) {
                            list($id) = $routes->tokenizer->parseToken($segment);
                            if (empty($paths[$i])) $route['params'][$id] = $route['defaults'][$id] ?? null;
                            else $route['params'][$id] = $paths[$i];
                            if (is_numeric($route['params'][$id])) $route['params'][$id] = $route['params'][$id] + 0;
                        }
                    }

                    if (isset($i) && isset($paths[$i+1]) && isset($id)) {
                        $route['params'][$id] = $route['params'][$id]
                            . '/' . implode('/', array_slice($paths, $i + 1));
                    }

                    return RouteRecord::fromArray($route);
                };

                foreach ($routes->dynamicRoutes as $route) {
                    if (preg_match($route['regexp'], $path) === 1) {
                        if (in_array($requestMethod, $route['methods'])) {
                            return $extractRouteParameters($route, $path);
                        }
                    }
                }

                if (!empty($routes->routes)) return parent::match($routes, $uri, $requestMethod);
                return null;
            }
            public function getRoute(string $name): ?RouteRecord
            {
                foreach ($this->staticRoutes as $routeData) {
                    if ($routeData['name'] === $name) {
                        return RouteRecord::fromArray($routeData);
                    }
                }

                foreach ($this->dynamicRoutes as $routeData) {
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
                foreach ($this->staticRoutes as $routeData) {
                    yield RouteRecord::fromArray($routeData);
                }

                foreach ($this->dynamicRoutes as $routeData) {
                    yield RouteRecord::fromArray($routeData);
                }

                if ($this->routes !== []) yield from parent::getIterator();
            }
        };
    }

    public static function createFromContainer(ContainerInterface $container): static
    {
        return new static($container->get(Tokenizer::class));
    }
}
