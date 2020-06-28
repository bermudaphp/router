<?php


namespace Bermuda\Router;


use Psr\Http\Message\ServerRequestInterface;


/**
 * Class Router
 * @package Bermuda\Router
 */
class Router implements RouterInterface
{
    private RouteMap $routes;

    public function __construct(RouteFactoryInterface $factory = null)
    {
        $this->routes = new RouteMap($factory ?? new RouteFactory());
    }

    /**
     * @param string $method
     * @param string $uri
     * @return RouteInterface
     * @throws Exception\RouteNotFoundException
     * @throws Exception\RouterException
     */
    public function match(string $method, string $uri): RouteInterface
    {
        $path = $this->parseUri($uri);

        foreach ($this->routes as $route)
        {
            if(preg_match($this->regexp($route), $path) === 1)
            {
                if(!in_array($method, $route->getMethods()))
                {
                    Exception\ExceptionFactory::notAllows($method, $route->getMethods())->throw();
                }

                return $route->withAttributes(
                    $this->parseAttributes($route, $path)
                );
            }
        }

        Exception\ExceptionFactory::notFound()
            ->setPath($path)->throw();
    }

    /**
     * @param string $uri
     * @return string
     */
    private function parseUri(string $uri) : string
    {
        return $this->filter(parse_url($uri, PHP_URL_PATH));
    }

    /**
     * @param $path
     * @return string
     */
    private function filter(string $path): string
    {
        return \preg_replace_callback('/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/]++|%(?![A-Fa-f0-9]{2}))/', function (array $match)
        {
            return rawurldecode($match[0]);
        }, $path);
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
     * @throws Exception\GeneratorException
     * @throws Exception\RouteNotFoundException
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
                    ExceptionFactory::pathAttributeMissing($attribute)->throw();
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
