<?php

namespace Bermuda\Router;

use Bermuda\Router\Locator\RouteLocatorInterface;
use Psr\Container\ContainerInterface;

/**
 * The Router class is responsible for matching incoming HTTP requests to routes
 * and for generating URLs from route names and parameters.
 */
final class Router
{
    // This property holds the current matched route.
    private(set) ?RouteRecord $route = null;

    /**
     * Constructor.
     *
     * @param Matcher   $matcher   The matcher service responsible for matching routes.
     * @param Generator $generator The URL generator service.
     * @param RouteMap  $routes    The collection of defined routes.
     */
    public function __construct(
        private Matcher   $matcher,
        private Generator $generator, 
        private RouteMap  $routes
    ) {
    }

    /**
     * Matches an incoming URI and request method against the route map.
     *
     * @param string $uri           The request URI.
     * @param string $requestMethod The HTTP request method (e.g., GET, POST).
     *
     * @return ?RouteRecord The matched route record or null if no match found.
     */
    public function match(string $uri, string $requestMethod):? RouteRecord
    {
        return $this->route = $this->matcher->match($this->routes, $uri, $requestMethod);
    }

    /**
     * Generates a URL for a named route.
     *
     * @param string $name   The name of the route.
     * @param array  $params Parameters to fill in the route placeholders.
     *
     * @throws Exception\GeneratorException if URL generation fails.
     *
     * @return string The generated URL.
     */
    public function generate(string $name, array $params = []): string
    {
        return $this->generator->generate($this->routes, $name, $params);
    }

    /**
     * Returns a new Router instance with a different set of routes.
     *
     * @param RouteMap $routes The new route map to use.
     *
     * @return self A new instance of Router with the specified routes.
     */
    public function withRoutes(RouteMap $routes): self
    {
        $copy = clone $this;
        $copy->routes = $routes;
        
        return $copy;
    }

    /**
     * Returns a clone of the current route map.
     *
     * @return RouteMap A clone of the current route map.
     */
    public function getRoutes(): RouteMap
    {
        return clone $this->routes;
    }

    /**
     * Creates a new Router instance using default routes.
     *
     * @return self A new Router with default route settings.
     */
    public static function withDefaults(): self
    {
        return self::fromDnf(new Routes());
    }

    /**
     * Creates a new Router instance from a dependency that implements Matcher, Generator, and RouteMap.
     *
     * @param Matcher&Generator&RouteMap $routes An instance that implements all three interfaces.
     *
     * @return self A new Router using the provided dependency.
     */
    public static function fromDnf(Matcher&Generator&RouteMap $routes): self
    {
        return new self($routes, $routes, $routes);
    }

    /**
     * Creates a new Router instance from a container.
     *
     * @param ContainerInterface $container The PSR container to retrieve dependencies.
     *
     * @return self A new Router instance using services from the container.
     */
    public static function createFromContainer(ContainerInterface $container): self
    {
        $locator = $container->get(RouteLocatorInterface::class);
        $routes = $locator->getRoutes();
        
        $isDnf = array_all([Matcher::class, Generator::class, RouteMap::class],
            fn($i) => $routes instanceof $i
        );

        if ($isDnf) return self::fromDnf($routes);

        return new self(
            $container->get(Matcher::class),
            $container->get(Generator::class),
            $routes
        );
    }

}
