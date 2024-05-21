<?php

namespace Bermuda\Router\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DispatchRouteMiddleware implements MiddlewareInterface
{
    public function __construct(private ?RequestHandlerInterface $fallbackHandler = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (($route = $request->getAttribute(RouteMiddleware::class)) instanceof RouteMiddleware) {
            return $route->process($request, $handler);
        }

        if ($this->fallbackHandler) return $this->fallbackHandler->handle($request);

        return $handler->handle($request);
    }

    public function setFallbackHandler(?RequestHandlerInterface $fallbackHandler): self
    {
        $this->fallbackHandler = $fallbackHandler;
        return $this;
    }
}
