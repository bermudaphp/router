<?php

namespace Bermuda\Router\Middleware;

use Bermuda\Router\Route;
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
    public function __construct(private MiddlewareFactoryInterface $middlewareFactory, private Route $route)
    {
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call(string $name, array $arguments)
    {
        try {
            return $this->route->{$name}(...$arguments);
        } catch (\Throwable) {
            throw new \BadMethodCallException('Bad method call: '. $name);
        }
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->process($request, new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $req): ResponseInterface
            {
                ExceptionFactory::emptyHandler()->throw();
            }
        });
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
     * @param MiddlewareFactoryInterface $middlewareFactory
     * @param ServerRequestInterface $request
     * @param Route $route
     * @return ServerRequestInterface
     */
    public static function modify(MiddlewareFactoryInterface $middlewareFactory, ServerRequestInterface $request, Route $route): ServerRequestInterface
    {
        return $request->withAttribute(self::class, new self($middlewareFactory, $route));
    }
}
