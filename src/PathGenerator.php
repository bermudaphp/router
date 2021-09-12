<?php

namespace Bermuda\Router;

final class PathGenerator implements Generator
{
    use AttributeNormalizer;

    /**
     * @param RouteMap $routes
     * @param string $name
     * @param array $attributes
     * @return string
     * @throws Exception\GeneratorException
     * @throws Exception\RouteNotFoundException
     */
    public function generate(RouteMap $routes, string $name, array $attributes = []): string
    {
        $segments = explode('/', $routes->getRoute($name)->getPath());

        $path = '';

        foreach ($segments as $segment) {
            if (empty($segment)) {
                continue;
            }

            $path .= '/';

            if ($this->isAttribute($segment)) {
                $attribute = $this->normalize($segment);

                if (!$this->isOptional($segment)) {
                    if (!array_key_exists($attribute, $attributes)) {
                        Exception\ExceptionFactory::pathAttributeMissing($attribute)->throw();
                    }

                    $path .= $attributes[$attribute];
                } elseif (array_key_exists($attribute, $attributes)) {
                    $path .= $attributes[$attribute];
                }
            } else {
                $path .= $segment;
            }
        }

        return empty($path) ? '/' : rtrim($path, '/');
    }
}
