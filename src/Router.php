<?php

namespace Bermuda\Router;

use Psr\Http\Message\ServerRequestInterface;
use Bermuda\Router\Exception\RouteNotFoundException;
use Bermuda\Router\Exception\MethodNotAllowedException;

/**
 * Class Router
 * @package Bermuda\Router
 */
final class Router implements RouterInterface, RouteMap
{
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
     * @return RouteInterface[]
     */
    public function toArray(): array 
    {
        return $this->getRoutes();
    }
    
    /**
     * @return RouteInterface[]
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
        if (is_array($route))
        {
            $route = Route::makeOf($route);
        }
        
        $this->routes[$route->getName()] = $route;
        
        return $this;
    }
     
    /**
     * @param string $prefix
     * @param callable $callback
     * @param array $mutators
     * @return $this
     */
    public function group($prefix, callable $callback): RouteMap
    {
        $callback($map = new self);
        
        if (is_array($prefix))
        {
            $mutators = $prefix;
            $prefix = array_pull($mutators, 'prefix');
            
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
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $mutators
     * @return RouteMap
     */
    public function get(string $name, string $path, $handler, array $mutators = []): RouteMap
    {
        return $this->add(array_merge(compact('name', 'path', 'handler'), $mutators, ['methods' => 'GET']));
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $mutators
     * @return RouteMap
     */
    public function post(string $name, string $path, $handler, array $mutators = []): RouteMap
    {
        return $this->add(array_merge(compact('name', 'path', 'handler'), $mutators, ['methods' => 'POST']));
    }
  
    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $mutators
     * @return RouteMap
     */
    public function delete(string $name, string $path, $handler, array $mutators = []): RouteMap
    {
        return $this->add(array_merge(compact('name', 'path', 'handler'), $mutators, ['methods' => 'DELETE']));
    }
    
    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $mutators
     * @return RouteMap
     */
    public function put(string $name, string $path, $handler, array $mutators = []): RouteMap
    {
        return $this->add(array_merge(compact('name', 'path', 'handler'), $mutators, ['methods' => 'PUT']));
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $mutators
     * @return RouteMap
     */
    public function patch(string $name, string $path, $handler, array $mutators = []): RouteMap
    {
        return $this->add(array_merge(compact('name', 'path', 'handler'), $mutators, ['methods' => 'PATCH']));
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $mutators
     * @return RouteMap
     */
    public function options(string $name, string $path, $handler, array $mutators = []): RouteMap
    {
        return $this->add(array_merge(compact('name', 'path', 'handler'), $mutators, ['methods' => 'OPTIONS']));
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $mutators
     * @return RouteMap
     */
    public function any(string $name, string $path, $handler, array $mutators = []): RouteMap
    {
        return $this->add(array_merge(compact('name', 'path', 'handler'), ['methods' => Route::$default_http_methods], $mutators));
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
        unset($matches[0]);
        $attributes = [];

        foreach (explode('/', $route->getPath()) as $i => $segment)
        {
            if ($this->isAttribute($segment))
            {
                $attributes[$this->normalize($segment)] = array_shift($matches);
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
        
        return $segment[0] === '{' && $segment[strlen($segment) - 1] === '}';
    }

    /**
     * @param string $placeholder
     * @return string
     */
    private function normalize(string $placeholder): string
    {
        return trim($placeholder, '{}');
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

                if (!array_key_exists($attribute, $attributes))
                {
                    Exception\ExceptionFactory::pathAttributeMissing($attribute)->throw();
                }

                $path .= $attributes[$attribute];
            }

            else 
            {
                $path .= $segment;
            }
        }

        return empty($path) ? '/' : $path;
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
}
