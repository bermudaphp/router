<?php


namespace Bermuda\Router\Middleware;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;


/**
 * Class DispatchRouteMiddleware
 * @package Bermuda\Router;
 */
class DispatchRouteMiddleware implements MiddlewareInterface
{
    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /**
         * @var RouteMiddleware $route
         */
        if (($route = $request->getAttribute(RouteMiddleware::class)) != null)
        {
            return $route->process($request, $handler);
        }

        return $handler->handle($request);
    }
}
