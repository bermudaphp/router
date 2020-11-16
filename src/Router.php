<?php


namespace Bermuda\Router;


use Psr\Http\Message\ServerRequestInterface;
use Bermuda\Router\Exception\ExceptionFactory;
use Bermuda\Router\Exception\MethodNotAllowedException;


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
     * @param string $requestMethod
     * @param string $uri
     * @return RouteInterface
     * @throws Exception\RouteNotFoundException
     * @throws Exception\MethodNotAllowedException
     */
    public function match(string $requestMethod, string $uri): RouteInterface
    {
        $path = $this->getPath($uri);

        foreach ($this->routes as $route)
        {
            if (preg_match($this->regexp($route), $path) === 1)
            {
                if (!in_array(strtoupper($requestMethod), $route->methods()))
                {
                    if (!isset($e))
                    {
                        $e = MethodNotAllowedException::make($path, $requestMethod);
                    }

                    $e->addAllowedMethods($route->methods());
                    continue;
                }

                return $route->withAttributes(
                    $this->parseAttributes($route, $path)
                );
            }
        }

        throw $e ?? ExceptionFactory::notFound()
                ->setPath($path);
    }

    /**
     * @param string $uri
     * @return string
     */
    private function gatPath(string $uri): string
    {
        return rawurldecode(parse_url($uri, PHP_URL_PATH)));
    }

    /**
     * @param Route $route
     * @return string
     */
    private function regexp(RouteInterface $route): string
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
    private function parseAttributes(RouteInterface $route, string $path): array
    {
        $attributes = [];
        $segments = explode('/', $path);

        foreach (explode('/', $route->getPath()) as $i => $segment)
        {
            if ($this->isAttribute($segment))
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
        $segments = explode('/',
            $this->routes->route($name)->getPath()
        );

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

    /**
     * @return RouteMap
     */
    public function getRoutes(): RouteMap
    {
        return $this->routes;
    }
}
