<?php

namespace Bermuda\Router;

final class RouteMatcher implements Matcher
{
    use AttributeNormalizer;

    /**
     * @param RouteMap $routes
     * @param string $requestMethod
     * @param string $uri
     * @return Route
     * @throws Exception\RouteNotFoundException
     * @throws Exception\MethodNotAllowedException
     */
    public function match(RouteMap $routes, string $requestMethod, string $uri): Route
    {
        foreach ($routes as $route) {
            if (preg_match($this->buildRegexp($route), $path = $this->getPath($uri), $matches) === 1) {
                if (in_array(strtoupper($requestMethod), $route->methods())) {
                    return $this->parseAttributes($route, $matches);
                }

                ($e ?? $e = Exception\MethodNotAllowedException::make($path, $requestMethod))
                    ->addAllowedMethods($route->methods());
            }
        }

        throw $e ?? (new Exception\RouteNotFoundException())->setPath($path ?? $this->getPath($uri));
    }

    /**
     * @param Route $route
     * @return string
     */
    private function buildRegexp(Route $route): string
    {
        if (($path = $route->getPath()) === '' || $path === '/') {
            return '#^/$#';
        }

        $pattern = '#^';

        $segments = explode('/', $path);

        foreach ($segments as $segment) {
            if (empty($segment)) {
                continue;
            }

            if ($this->isOptional($segment)) {
                $pattern .= '/??(';

                if ($this->isAttribute($segment)) {
                    $token = $this->normalize($segment);
                    $pattern .= $route->tokens()[$token] ?? '(.+)';
                } else {
                    $pattern .= $segment;
                }

                $pattern .= ')??';
                continue;
            }

            $pattern .= '/';

            if ($this->isAttribute($segment)) {
                $token = $this->normalize($segment);
                $pattern .= $route->tokens()[$token] ?? '(.+)';
            } else {
                $pattern .= $segment;
            }

        }

        return $pattern . '/?$#';
    }

    /**
     * @param string $uri
     * @return string
     */
    private function getPath(string $uri): string
    {
        return rawurldecode(parse_url($uri, PHP_URL_PATH));
    }

    private function parseAttributes(Route $route, array $matches): Route
    {
        if (count($matches) > 1) {
            array_shift($matches);
        } else {
            return $route;
        }

        foreach (explode('/', $route->getPath()) as $segment) {
            if ($this->isAttribute($segment)) {
                $attributes[$this->normalize($segment)] = ltrim(array_shift($matches), '/');
            }
        }

        return $route->withAttributes($attributes);
    }
}
