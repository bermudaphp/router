<?php


namespace Bermuda\Router;


use Bermuda\Arrayable;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Bermuda\Router\Exception\ExceptionFactory;
use Bermuda\Router\Middleware\RedirectMiddleware;


/**
 * Class RouteMap
 * @package Bermuda\Router
 */
final class RouteMap implements \IteratorAggregate, \Countable, Arrayable
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
        return isset($this->routes[$name]);
    }

    /**
     * @param string $name
     * @return RouteInterface
     * @throws Exception\RouteNotFoundException
     */
    public function route(string $name): RouteInterface
    {
        if (!$this->has($name))
        {
            ExceptionFactory::notFound()->setName($name)->throw();
        }

        return $this->routes[$name];
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
        $callback($routes = new self($this->factory));
     
        foreach ($routes as $route)
        {     
            $this->add($route->addPrefix($prefix));
        }

        return $routes;
    }
    
    /**
     * @param array $tokens
     * @return RouteMap
     */
    public function tokens(array $tokens): self
    {    
        foreach ($this->routes as $route)
        {     
            $route->tokens($tokens);
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
     * @param string $routeNameOrPath
     * @return RouteInterface
     */
    public function redirect(string $name, string $path, string $routeNameOrPath, bool $permanent = false): RouteInterface
    {
        return $this->any($name, $path, function(ContainerInterface $c) use ($routeNameOrPath, $permanent): MiddlewareInterface
        {
            if ($this->has($routeNameOrPath))
            {
                return new RedirectMiddleware($this->route($routeNameOrPath)->getPath(), $c->get(ResponseFactoryInterface::class), $permanent);
            }
            
            return new RedirectMiddleware($routeNameOrPath, $c->get(ResponseFactoryInterface::class), $permanent);
        });
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
    public function post(string $name, string $path, $handler): RouteInterface
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
    public function delete(string $name, string $path, $handler): RouteInterface
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
    public function put(string $name, string $path, $handler): RouteInterface
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
    public function patch(string $name, string $path, $handler): RouteInterface
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
    public function options(string $name, string $path, $handler): RouteInterface
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
     * @return RouteInterface
     */
    public function any(string $name, string $path, $handler): RouteInterface
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
