<?php

namespace Bermuda\Router\Middleware;

use Bermuda\Router\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Bermuda\Router\Exception\ExceptionFactory;
use Bermuda\MiddlewareFactory\MiddlewareFactoryInterface;

final class RouteMiddleware implements MiddlewareInterface, RequestHandlerInterface
{
    public function __construct(private MiddlewareFactoryInterface $middlewareFactory, private Route $route)
    {
    }

     /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->middlewareFactory->make($this->route->getHandler())
            ->process($request, $handler);
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->process($request, new class implements RequestHandlerInterface
        {
            public function handle(ServerRequestInterface $req): ResponseInterface
            {
                ExceptionFactory::emptyHandler()->throw();
            }
        });
    }
    
    /**
     * @inheritDoc
     */
    public function getRoute(): Route
    {
        return $this->route;
    }
}
