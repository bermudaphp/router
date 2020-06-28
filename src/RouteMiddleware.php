<?php


namespace Bermuda\Router;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Bermuda\MiddlewareFactory\MidllewareFactoryInterface;


/**
 * Class RouteMiddleware
 * @package Bermuda\Router
 */
class RouteMiddleware implements MiddlewareInterface, RequestHandlerInterface, RouteInterface
{
    private RouteInterface $route;
    private MidllewareFactoryInterface $factory;

    public function __construct(MidllewareFactoryInterface $factory, RouteInterface $route)
    {
        $this->route = $route;
        $this->factory = $factory;
    }

     /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->resolver->resolve($this->route->getHandler())
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
    public function getName(): string
    {
        return $this->route->getName();
    }

    /**
     * @inheritDoc
     */
    public function getHandler()
    {
        return $this->route->getHandler();
    }

    /**
     * @inheritDoc
     */
    public function addPrefix(string $prefix): RouteInterface
    {
        $this->route->addPrefix($prefix);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addSuffix(string $suffix): RouteInterface
    {
        $this->route->addSuffix($suffix);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        return $this->route->getPath();
    }

    /**
     * @inheritDoc
     */
    public function tokens(array $tokens = []): array
    {
        return $this->route->tokens($tokens);
    }

    /**
     * @inheritDoc
     */
    public function methods(array $methods = []): array
    {
        return $this->route->methods($methods);
    }

    /**
     * @inheritDoc
     */
    public function getAttributes(): array
    {
       return $this->route->getAttributes();
    }

    /**
     * @inheritDoc
     */
    public function withAttributes(array $attributes): RouteInterface
    {
        $this->route = $this->route->withAttributes($attributes);
        return clone $this;
    }
    
    /**
     * @inheritDoc
     */
    public function before($middleware) : RouteInterface
    {
        $this->route->before($middleware);
        return $this;
    }
    
    /**
     * @inheritDoc
     */
    public function after($middleware) : RouteInterface
    {
        $this->route->after($middleware);
        return $this;
    }
}
