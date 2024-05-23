<?php

namespace Bermuda\Router\Middleware;

use Bermuda\Router\MatchedRoute;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Bermuda\Router\Exception\ExceptionFactory;
use Bermuda\MiddlewareFactory\MiddlewareFactoryInterface;

/**
 * @mixin Route
 */
final class RouteMiddleware implements MiddlewareInterface, RequestHandlerInterface
{
    public function __construct(
        private readonly MiddlewareFactoryInterface $middlewareFactory, 
        public readonly MatchedRoute $route
    ) {
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->process($request, new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $req): ResponseInterface
            {
                throw new \RuntimeException('Empty request handler');
            }
        });
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->middlewareFactory->make($this->route->handler)
            ->process($request, $handler);
    }

    public function setRequestAttribute(ServerRequestInterface $request): ServerRequestInterface
    {
        return $request->withAttribute(RouteMiddleware::class, $this);
    }
}
