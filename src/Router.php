<?php

namespace Bermuda\Router;

use Bermuda\Arr;
use Bermuda\String\Str;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Message\ServerRequestInterface;
use Bermuda\Router\Exception\RouteNotFoundException;
use Bermuda\Router\Exception\MethodNotAllowedException;

/**
 * Class Router
 * @package Bermuda\Router
 */
final class Router implements RouterInterface, RouteMap
{
    /**
     * @var Route
     */
    private array $routes = [];

    public static function makeOf(array $routes): self
    {
        $self = new self;
        
        foreach($routes as $route)
        {
            $self->add($route);
        }
        
        return $self;
    }
    
    /**
     * @return Route[]
     */
    public function toArray(): array 
    {
        return $this->getRoutes();
    }
    
    /**
     * @return Route[]
     */
    public function getRoutes(): array 
    {
        return $this->routes;
    }
    
    /**
     * @param Route|array $route
     * @return $this
     */
    public function add($route): RouteMap 
    {
        !is_array($route) ?: $route = Route::makeOf($route);
        $this->routes[$route->getName()] = $route;
        
        return $this;
    }
     
    /**
     * @param string|array $prefix
     * @param callable $callback
     * @return $this
     */
    public function group($prefix, callable $callback): RouteMap
    {
        $callback($map = new self);
        
        if (is_array($prefix))
        {
            $mutators = $prefix;
            $prefix = Arr::pull($mutators, 'prefix');
            
            foreach($map->routes as $route)
            {
                foreach($mutators as $mutator => $v)
                {
                    $route = $route->{$mutator}($v);
                }
                
                $this->add($route->withPrefix($prefix));
            }
            
            return $this;
        }
        
        foreach($map->routes as $route)
        {
            $this->add($route->withPrefix($prefix));
        }
        
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function get($name, ?string $path = null, $handler = null): RouteMap
    {
        return $this->add($this->merge($name, $path, $handler, ['GET']));
    }

    /**
     * @inheritDoc
     */
    public function post($name, ?string $path = null, $handler = null): RouteMap
    {
        return $this->add($this->merge($name, $path, $handler, ['POST']));
    }

    /**
     * @inheritDoc
     */
    public function delete($name, ?string $path = null, $handler = null): RouteMap
    {
        return $this->add($this->merge($name, $path, $handler, ['DELETE']));
    }

    /**
     * @inheritDoc
     */
    public function put($name, ?string $path = null, $handler = null): RouteMap
    {
        return $this->add($this->merge($name, $path, $handler, ['PUT']));
    }

    /**
     * @inheritDoc
     */
    public function patch($name, ?string $path = null, $handler = null): RouteMap
    {
        return $this->add($this->merge($name, $path, $handler, ['PATCH']));
    }

    /**
     * @inheritDoc
     */
    public function options($name, ?string $path = null, $handler = null): RouteMap
    {
        return $this->add($this->merge($name, $path, $handler, ['OPTIONS']));
    }

    /**
     * @inheritDoc
     */
    public function any($name, ?string $path = null, $handler = null): RouteMap
    {
        return $this->add($this->merge($name, $path, $handler));
    }

    /**
     * @inheritDoc
     */
    public function resource($name, ?string $path = null, $resource = null): RouteMap
    {
        $route = $this->merge($name, $path, $resource, []);

        if (!is_subclass_of($route['handler'], ResourceInterface::class))
        {
            throw new \RuntimeException(sprintf('Handler must be subclass of %s', ResourceInterface::class));
        }

        $showHandler = !is_string($resource) ? [$route['handler'], 'show'] : $route['handler'] . '@show';
        $createHandler = !is_string($resource) ? [$route['handler'], 'create'] : $route['handler'] . '@create';
        $updateHandler = !is_string($resource) ? [$route['handler'], 'update'] : $route['handler'] . '@update';
        $deleteHandler = !is_string($resource) ? [$route['handler'], 'delete'] : $route['handler'] . '@delete';

        $this->add(array_merge($route, ['name' => $route['name'] . '.create', 'handler' => $createHandler, 'methods' => [RequestMethodInterface::METHOD_POST]]));
        $this->add(array_merge($route, ['name' => $route['name'] . '.show', 'handler' => $showHandler, 'path' => $route['path'] . '/?{id}', 'methods' => [RequestMethodInterface::METHOD_GET]]));
        $this->add(array_merge($route, ['name' => $route['name'] . '.delete', 'handler' => $deleteHandler, 'path' => $route['path'] . '/{id}', 'methods' => [RequestMethodInterface::METHOD_DELETE]]));
        $this->add(array_merge($route, ['name' => $route['name'] . '.update', 'handler' => $updateHandler, 'path' => $route['path'] . '/{id}', 'methods' => [RequestMethodInterface::METHOD_PUT]]));

        return $this;
    }

    /**
     * @param string $requestMethod
     * @param string $uri
     * @return Route
     * @throws Exception\RouteNotFoundException
     * @throws Exception\MethodNotAllowedException
     */
    public function match(string $requestMethod, string $uri): Route
    {
        foreach ($this->routes as $route)
        {
            if (preg_match($this->buildRegexp($route), $path = $this->getPath($uri), $matches) === 1)
            {
                if (in_array(strtoupper($requestMethod), $route->methods()))
                {
                    return $this->parseAttributes($route, $matches);
                }

                ($e ?? $e = MethodNotAllowedException::make($path, $requestMethod))
                    ->addAllowedMethods($route->methods());
            }
        }

        throw $e ?? (new RouteNotFoundException())->setPath($path);
    }

    /**
     * @param string $segment
     * @return bool
     */
    private function isOptional(string $segment): bool
    {
        return Str::contains($segment, '?');
    }

    /**
     * @param string $uri
     * @return string
     */
    private function getPath(string $uri): string
    {
        return rawurldecode(parse_url($uri, PHP_URL_PATH));
    }

    /**
     * @param Route $route
     * @return string
     */
    private function buildRegexp(Route $route): string
    {
        if (($path = $route->getPath()) === '' || $path === '/')
        {
            return '#^/$#';
        }

        $pattern = '#^';

        $segments = explode('/', $path);

        foreach ($segments as $segment)
        {
            if (empty($segment))
            {
                continue;
            }

            if ($this->isOptional($segment))
            {
                $pattern .= '/??(';

                if ($this->isAttribute($segment))
                {
                    $token = $this->normalize($segment);
                    $pattern .= $route->tokens()[$token] ?? '(.+)';
                }

                else
                {
                    $pattern .= $segment;
                }

                $pattern .= ')??';
                continue;
            }

            $pattern .= '/';

            if ($this->isAttribute($segment))
            {
                $token = $this->normalize($segment);
                $pattern .= $route->tokens()[$token] ?? '(.+)';
            }

            else 
            {
                $pattern .= $segment;
            }

        }

        return $pattern . '/?$#';
    }

    /**
     * @param Route $route
     * @param string $path
     * @return array
     */
    private function parseAttributes(Route $route, array $matches): Route
    {
        $attributes = [];
        array_shift($matches);
       
        foreach (explode('/', $route->getPath()) as $segment)
        {
            if ($this->isAttribute($segment))
            {
                $attributes[$this->normalize($segment)] = ltrim(array_shift($matches), '/');
            }
        }

        return $route->withAttributes($attributes);
    }

    /**
     * @param string $segment
     * @return bool
     */
    private function isAttribute(string $segment): bool
    {
        if (empty($segment))
        {
            return false;
        }

        return ($segment[0] === '{' || $segment[0] === '?') && $segment[strlen($segment) - 1] === '}';
    }

    /**
     * @param string $placeholder
     * @return string
     */
    private function normalize(string $placeholder): string
    {
        return trim($placeholder, '?{}');
    }

    /**
     * @param string $name
     * @param array $attributes
     * @return string
     * @throws Exception\GeneratorException
     * @throws Exception\RouteNotFoundException
     */
    public function generate(string $name, array $attributes = []): string
    {
        $segments = explode('/', $this->getRoute($name)->getPath());

        $path = '';

        foreach ($segments as $segment)
        {
            if (empty($segment))
            {
                continue;
            }

            $path .= '/';

            if ($this->isAttribute($segment))
            {
                $attribute = $this->normalize($segment);

                if (!$this->isOptional($segment))
                {
                    if (!array_key_exists($attribute, $attributes))
                    {
                        Exception\ExceptionFactory::pathAttributeMissing($attribute)->throw();
                    }

                    $path .= $attributes[$attribute];
                }

                elseif(array_key_exists($attribute, $attributes))
                {
                    $path .= $attributes[$attribute];
                }
            }

            else 
            {
                $path .= $segment;
            }
        }

        return empty($path) ? '/' : rtrim($path, '/');
    }
    
    private function getRoute(string $name): Route
    {
        $route = $this->routes[$name] ?? null;
        
        if ($route)
        {
            return $route;
        }
        
        throw (new RouteNotFoundException())->setName($name);
    }
    
    private function merge($name, $path, $handler, ?array $methods = null): array
    {
        $methods != null ?: $methods = Route::$requestMethods;
        
        if (is_array($name))
        {
            $data = $name;
            
            if ($path != null)
            {
                $data['path'] = $path;
            }
            
            if ($handler != null)
            {
                $data['handler'] = $handler;
            }

            $data['methods'] = $methods;

            return $data;
        }

        return compact('name', 'path', 'handler', 'methods');
    }
}
