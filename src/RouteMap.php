<?php


namespace Bermuda\Router;


use Bermuda\Reducible\Arrayble;


/**
 * Class RouteMap
 * @package Bermuda\Router
 */
class RouteMap implements \IteratorAggregate, \Countable, Arrayble
{
    /**
     * @var RouteInterface[]
     */
    private array $routes = [];
    private RouteFactoryInterface $factory;

    public function __construct(RouteFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @return RouteInterface[]
     */
    public function getIterator() : \Generator
    {
        foreach ($this->routes as $name => $route)
        {
            yield $name => $route;
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name) : bool
    {
        return array_key_exists($name, $this->routes);
    }

    /**
     * @param string $name
     * @return RouteInterface
     * @throws Exception\RouteNotFoundException
     */
    public function route(string $name) : RouteInterface
    {
        $route = $this->routes[$name] ?? null;

        if (!$route)
        {
            ExceptionFactory::notFound()
                ->setName($name)->throw();
        }

        return $route;
    }
    
    /**
     * @param RouteInterface $route
     * @return RouteInterface
     */
    public function add(RouteInterface $route) : RouteInterface
    {
        return $this->routes[$route->getName()] = $route;
    }

    /**
     * @param string $prefix
     * @return RouteMap
     */
    public function addPrefix(string $prefix) : self
    {
        $routes = $this->routes;

        foreach ($routes as $route)
        {
            $route->addPrefix($prefix);
        }

        return $this;
    }

    /**
     * @param string $prefix
     * @param callable $callback
     * @return RouteMap
     */
    public function group(string $prefix, callable $callback, array $middleware = []) : self
    {
        $routes = new static($this->factory);

        $callback($routes);
        
        $after  = $middleware['after'] ?? [];
        unset($middleware['after']);
        $before = $middleware['before'] ?? $middleware;

        /**
         * @var RouteInterface $route
         */
        foreach ($routes as $route)
        {
            if($after != [])
            {
                $route->after($after);
            }
            
            if($before != [])
            {
                $route->before($before);
            }
            
            $this->add($route->addPrefix($prefix));
        }

        return $this;
    }

    /**
     * @return RouteInterface[]
     */
    public function toArray(): array
    {
        return $this->routes;
    }

    /**
     * @return bool
     */
    public function isEmpty() : bool
    {
        return $this->routes == [];
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @return RouteInterface
     */
    public function get(string $name, string $path, $handler): RouteInterface
    {
        $route = $this->add($this->factory->make($name, $path, $handler));
        
        $route->tokens(RouteInterface::tokens);
        $route->methods([RequestMethodInterface::METHOD_GET]);
        
        return $route;
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @return RouteInterface
     */
    public function post(string $name, string $path, $handler): self
    {
        $route = $this->add($this->factory->make($name, $path, $handler));
        
        $route->tokens(RouteInterface::tokens);
        $route->methods([RequestMethodInterface::METHOD_POST]);
        
        return $route;
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @return RouteInterface
     */
    public function delete(string $name, string $path, $handler): self
    {
        $route = $this->add($this->factory->make($name, $path, $handler));
        
        $route->tokens(RouteInterface::tokens);
        $route->methods([RequestMethodInterface::METHOD_DELETE]);
        
        return $route;
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @return RouteInterface
     */
    public function put(string $name, string $path, $handler): self
    {
        $route = $this->add($this->factory->make($name, $path, $handler));
        
        $route->tokens(RouteInterface::tokens);
        $route->methods([RequestMethodInterface::METHOD_PUT]);
        
        return $route;
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @return RouteInterface
     */
    public function head(string $name, string $path, $handler): self
    {
        $route = $this->add($this->factory->make($name, $path, $handler));
        
        $route->tokens(RouteInterface::tokens);
        $route->methods([RequestMethodInterface::METHOD_HEAD]);
        
        return $route;
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @return RouteInterface
     */
    public function options(string $name, string $path, $handler): self
    {
        $route = $this->add($this->factory->make($name, $path, $handler));
        
        $route->tokens(RouteInterface::tokens);
        $route->methods([RequestMethodInterface::METHOD_OPTIONS]);
        
        return $route;
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $tokens
     * @return RouteInterface
     */
    public function any(string $name, string $path, $handler): self
    {
        $route = $this->add($this->factory->make($name, $path, $handler));
        
        $route->tokens(RouteInterface::tokens);
        $route->methods(RouteInterface::http_methods);
        
        return $route;
    }

    /**
     * @return int
     */
    public function count() : int
    {
        return count($this->routes);
    }
}
