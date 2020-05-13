<?php


namespace Lobster\Routing;


use Lobster\Reducible\Arrayble;
use Lobster\Routing\Exceptions\ExceptionFactory;
use Lobster\Routing\Exceptions\RouteNotFoundException;


/**
 * Class RouteMap
 * @package Lobster\Routing
 */
class RouteMap implements \IteratorAggregate, \Countable, Arrayble
{

    /**
     * @var Contracts\Route[]
     */
    private array $routes = [];

    /**
     * @var Contracts\RouteFactory
     */
    private Contracts\RouteFactory $factory;

    /**
     * RouteMap constructor.
     * @param RouteFactory $factory
     */
    public function __construct(Contracts\RouteFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @return Contracts\Route[]
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
     * @return Route
     * @throws RouteNotFoundException
     */
    public function route(string $name) : Route
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
     * @param Route $route
     * @return RouteMap
     */
    public function add(Contracts\Route ...$routes) : self
    {
        foreach ($routes as $route)
        {
            $this->routes[$route->getName()] = $route;
        }

        return $this;
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
     * @param callable $callable
     * @return RouteMap
     */
    public function group(string $prefix, callable $callable) : self
    {
        $routes = new static($this->factory);

        $callable($routes);

        /**
         * @var Contracts\Route $route
         */
        foreach ($routes as $route)
        {
            $this->add($route->addPrefix($prefix));
        }

        return $this;
    }

    /**
     * @return Contracts\Route[]
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
     * @param array $tokens
     * @return RouteMap
     */
    public function get(string $name, string $path, $handler, array $tokens = Contracts\Route::ROUTE_TOKENS): self
    {
        return $this->add($this->factory->get($name, $path, $handler, $tokens));
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $tokens
     * @return RouteMap
     */
    public function post(string $name, string $path, $handler, array $tokens = Contracts\Route::ROUTE_TOKENS): self
    {
        return $this->add($this->factory->post($name, $path, $handler, $tokens));
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $tokens
     * @return RouteMap
     */
    public function delete(string $name, string $path, $handler, array $tokens = Contracts\Route::ROUTE_TOKENS): self
    {
        return $this->add($this->factory->delete($name, $path, $handler, $tokens));
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $tokens
     * @return RouteMap
     */
    public function put(string $name, string $path, $handler, array $tokens = Contracts\Route::ROUTE_TOKENS): self
    {
        return $this->add($this->factory->put($name, $path, $handler, $tokens));
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $tokens
     * @return RouteMap
     */
    public function head(string $name, string $path, $handler, array $tokens = Contracts\Route::ROUTE_TOKENS): self
    {
        return $this->add($this->factory->head($name, $path, $handler, $tokens));
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $tokens
     * @return RouteMap
     */
    public function options(string $name, string $path, $handler, array $tokens = Contracts\Route::ROUTE_TOKENS): self
    {
        return $this->add($this->factory->options($name, $path, $handler, $tokens));
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $tokens
     * @return RouteMap
     */
    public function any(string $name, string $path, $handler, array $tokens = Contracts\Route::ROUTE_TOKENS): self
    {
        return $this->add($this->factory->any($name, $path, $handler, $tokens));
    }

    /**
     * @return int
     */
    public function count() : int
    {
        return count($this->routes);
    }
}
