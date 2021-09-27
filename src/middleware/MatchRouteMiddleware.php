<?php

namespace Bermuda\Router\Middleware;

use Bermuda\Router\Route;
use Bermuda\Router\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Bermuda\MiddlewareFactory\MiddlewareFactoryInterface;

final class MatchRouteMiddleware implements MiddlewareInterface
{
    private static ?Route $route = null;
    public function __construct(private MiddlewareFactoryInterface $middlewareFactory, private Router $router)
    {
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        self::$route = $this->router->match($request->getMethod(), (string) $request->getUri());

        foreach (self::$route->getAttributes() as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $request = RouteMiddleware::modify($this->middlewareFactory, $request, self::$route);

        return $handler->handle($request);
    }

    /**
     * @return Route|null
     */
    public static function getRoute():? Route
    {
        return self::$route;
    }
}
