<?php


namespace Lobster\Routing;


use Lobster\Resolver\Contracts\Resolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Lobster\Routing\Exceptions\RouteNotFoundException;
use Lobster\Routing\Exceptions\MethodNotAllowedException;


/**
 * Class MatchRoute
 * @package Lobster\Routing
 */
class MatchRouteMiddleware implements MiddlewareInterface
{
    private Resolver $resolver;
    private Contracts\Router $router;

    /**
     * MatchRoute constructor.
     * @param Resolver $resolver
     * @param Router $router
     */
    public function __construct(Resolver $resolver, Router $router)
    {
        $this->router = $router;
        $this->resolver = $resolver;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws RouteNotFoundException
     * @throws MethodNotAllowedException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $this->router->match($request->getMethod(),
            (string) $request->getUri());

        foreach ($route->getAttributes() as $name => $v)
        {
            $request = $request->withAttribute($name, $v);
        }

        $route = new RouteDecorator($this->resolver, $route);
        $request = $request->withAttribute(RouteDecorator::class, $route);

        return $handler->handle($request);
    }
}
