<?php

namespace Bermuda\Router\Middleware;

use Bermuda\Router\Route;
use Bermuda\Router\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Bermuda\MiddlewareFactory\MiddlewareFactoryInterface;

final class MatchRouteMiddleware implements MiddlewareInterface
{
    private static ?Route $route = null;
    public function __construct(private MiddlewareFactoryInterface $factory, private Router $router)
    {
    }
    
    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $this->router->match($request->getMethod(), (string)$request->getUri());

        foreach ($route->getAttributes() as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $route = new RouteMiddleware($this->factory, $route);
        $request = $request->withAttribute(RouteMiddleware::class, $route);

        return $handler->handle($request);
    }
    
    public static function getRoute():? Route
    {
        return self::$route;
    }
}
