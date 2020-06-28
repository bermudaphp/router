<?php


namespace Bermuda\Router;


use Bermuda\Reducible\Arrayble;
use Fig\Http\Message\RequestMethodInterface;



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
    public function getIterator(): \Generator
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
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->routes);
    }

    /**
     * @param string $name
     * @return RouteInterface
     * @throws Exception\RouteNotFoundException
     */
    public function route(string $name): RouteInterface
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
    public function add(RouteInterface $route): RouteInterface
    {
        return $this->routes[$route->getName()] = $route;
    }

    /**
     * @param string $prefix
     * @return RouteMap
     */
    public function addPrefix(string $prefix): self
    {
        foreach ($this->routes as $route)
        {
            $route->addPrefix($prefix);
        }

        return $this;
    }
    
    /**
     * @param mixed $middleware
     * @return RouteMap
     */
    public function after($middleware): self
    {
        foreach ($this->routes as $route)
        {
            $route->after($middleware);
        }

        return $this;
    }
    
    /**
     * @param mixed $middleware
     * @return RouteMap
     */
    public function before($middleware): self
    {
        foreach ($this->routes as $route)
        {
            $route->before($middleware);
        }

        return $this;
    }
    
    /**
     * @param string $prefix
     * @param callable $callback
     * @return RouteMap
     */
    public function group(string $prefix, callable $callback): self
    {
        $routes = new static($this->factory);

        $callback($routes);
        
        /**
         * @var RouteInterface $route
         */
        foreach ($routes as $route)
        {     
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
    public function isEmpty(): bool
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
        $data = [
            'name' => $name,
            'path' => $path,
            'handler' => $handler,
            'methods' => [RequestMethodInterface::METHOD_GET]
        ];
        
        return $this->add($this->factory->make($data));
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @return RouteInterface
     */
    public function post(string $name, string $path, $handler): self
    {
        $data = [
            'name' => $name,
            'path' => $path,
            'handler' => $handler,
            'methods' => [RequestMethodInterface::METHOD_POST]
        ];
        
        return $this->add($this->factory->make($data));
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @return RouteInterface
     */
    public function delete(string $name, string $path, $handler): self
    {
        $data = [
            'name' => $name,
            'path' => $path,
            'handler' => $handler,
            'methods' => [RequestMethodInterface::METHOD_DELETE]
        ];
        
        return $this->add($this->factory->make($data));
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @return RouteInterface
     */
    public function put(string $name, string $path, $handler): self
    {
        $data = [
            'name' => $name,
            'path' => $path,
            'handler' => $handler,
            'methods' => [RequestMethodInterface::METHOD_PUT]
        ];
        
        return $this->add($this->factory->make($data));
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @return RouteInterface
     */
    public function patch(string $name, string $path, $handler): self
    {
        $data = [
            'name' => $name,
            'path' => $path,
            'handler' => $handler,
            'methods' => [RequestMethodInterface::METHOD_PATCH]
        ];
        
        return $this->add($this->factory->make($data));
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @return RouteInterface
     */
    public function options(string $name, string $path, $handler): self
    {
       $data = [
            'name' => $name,
            'path' => $path,
            'handler' => $handler,
            'methods' => [RequestMethodInterface::METHOD_OPTIONS]
        ];
        
        return $this->add($this->factory->make($data));
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
        $data = [
            'name' => $name,
            'path' => $path,
            'handler' => $handler,
        ];
        
        return $this->add($this->factory->make($data));
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->routes);
    }
}
