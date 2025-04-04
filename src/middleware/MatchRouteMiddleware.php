<?php

namespace Bermuda\Router\Middleware;

use Bermuda\Router\Router;
use Bermuda\Router\RouteRecord;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Bermuda\MiddlewareFactory\MiddlewareFactoryInterface;

final class MatchRouteMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly MiddlewareFactoryInterface $middlewareFactory, 
        private readonly Router $router,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $this->router->match((string) $request->getUri(), $request->getMethod());
        if (!$route) return $handler->handle($request);

        $this->router->setCurrentRoute($route);
        
        foreach ($route->params as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }
        
        return $handler->handle((new RouteMiddleware($this->middlewareFactory, $route))->setRequestAttribute($request));
    }

    /**
     * @return RouteRecord|null
     */
    public function getRoute():? RouteRecord
    {
        return $this->router->getCurrentRoute();
    }

    public static function createFromContainer(ContainerInterface $container): MatchRouteMiddleware 
    {
        return new MatchRouteMiddleware(
            $container->get(MiddlewareFactoryInterface::class),
            $container->get(Router::class)
        );
    }
}
