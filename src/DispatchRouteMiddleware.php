<?php


namespace Lobster\Routing;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


/**
 * Class DispatchRoute
 * @package Lobster\Routing
 */
class DispatchRouteMiddleware implements MiddlewareInterface
{

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /**
         * @var RouteDecorator $route
         */
        if (($route = $request->getAttribute(RouteDecorator::class)) != null)
        {
            return $route->process($request, $handler);
        }

        return $handler->handle($request);
    }
}
