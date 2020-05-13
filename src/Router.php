<?php


namespace Lobster\Routing;


use Lobster\Factory\FactoryException;
use Lobster\Routing\Contracts\Route;
use Lobster\Routing\Exceptions\ExceptionFactory;
use Psr\Http\Message\ServerRequestInterface;
use Lobster\Routing\Exceptions\RouterException;
use Lobster\Routing\Exceptions\GeneratorException;
use Lobster\Routing\Exceptions\RouteNotFoundException;
use Lobster\Routing\Exceptions\MethodNotAllowedException;


/**
 * Class Router
 * @package Lobster\Routing
 */
class Router implements Contracts\Router
{
    /**
     * @var RouteMap
     */
    private RouteMap $routes;

    /**
     * Router constructor.
     * @param RouteFactory $factory
     */
    public function __construct(Contracts\RouteFactory $factory = null)
    {
        $this->routes = new RouteMap($factory ?? new RouteFactory());
    }

    /**
     * @param string $method
     * @param string $uri
     * @return Route
     * @throws RouteNotFoundException
     * @throws RouterException
     */
    public function match(string $method, string $uri): Route
    {
        $path = parse_url($uri, PHP_URL_PATH);

        foreach ($this->routes as $route)
        {
            if(preg_match($this->regexp($route), $path) === 1)
            {

                if(!in_array($method, $route->getMethods()))
                {
                    ExceptionFactory::notAllows($method, $route->getMethods())->throw();
                }

                return $route->withAttributes(
                    $this->parseAttributes($route, $path)
                );
            }
        }

        ExceptionFactory::notFound()
            ->setPath($path)->throw();
    }

    /**
     * @param Route $route
     * @return string
     */
    private function regexp(Contracts\Route $route) : string
    {
        if(($path = $route->getPath()) === '' || $path === '/')
        {
            return '#^/$#';
        }

        $pattern = '#^';

        $segments = explode('/', $path);

        foreach ($segments as $segment)
        {
            if(empty($segment))
            {
                continue;
            }

            $pattern .= '/';

            if($this->isAttribute($segment))
            {
                $token = $this->normalize($segment);
                $pattern .= $route->tokens()[$token] ?? '(.+)';
            }

            else {
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
    private function parseAttributes(Contracts\Route $route, string $path) : array
    {
        $attributes = [];
        $segments = explode('/', $path);

        foreach (explode('/', $route->getPath()) as $i => $segment)
        {
            if($this->isAttribute($segment))
            {
                $attributes[$this->normalize($segment)] = $segments[$i];
            }
        }

        return $attributes;
    }

    /**
     * @param string $segment
     * @return bool
     */
    private function isAttribute(string $segment) : bool
    {
        if(empty($segment))
        {
            return false;
        }
        
        return $segment[0] === '{' && $segment[strlen($segment) - 1] === '}';
    }

    /**
     * @param string $placeholder
     * @return string
     */
    private function normalize(string $placeholder) : string
    {
        return trim($placeholder, '{}');
    }

    /**
     * @param string $name
     * @param array $attributes
     * @return string
     * @throws Exceptions\GeneratorException
     * @throws RouteNotFoundException
     */
    public function generate(string $name, array $attributes = []): string
    {
        $segments = explode('/',
            $this->routes->route($name)->getPath()
        );

        $path = '';

        foreach ($segments as $segment)
        {
            if(empty($segment))
            {
                continue;
            }

            $path .= '/';

            if($this->isAttribute($segment))
            {
                $attribute = $this->normalize($segment);

                if(!array_key_exists($attribute, $attributes))
                {
                    ExceptionFactory::pathAttributeMissing($attribute);
                }

                $path .= $attributes[$attribute];
            }

            else {
                $path .= $segment;
            }
        }

        return empty($path) ? '/' : $path;
    }

    /**
     * @return RouteMap
     */
    public function getRoutes() : RouteMap
    {
        return $this->routes;
    }
}
