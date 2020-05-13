<?php


namespace Lobster\Routing;


use Lobster\Resolver\Contracts\Resolver;
use Lobster\Routing\Contracts\Route;
use Lobster\Routing\Exceptions\ExceptionFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


/**
 * Class RouteDecorator
 * @package Lobster\Routing
 */
class RouteDecorator implements MiddlewareInterface, RequestHandlerInterface, Contracts\Route
{
    private Resolver $resolver;
    private Contracts\Route $route;

    /**
     * RouteDecorator constructor.
     * @param Resolver $resolver
     * @param Contracts\Route $route
     */
    public function __construct(Resolver $resolver, Contracts\Route $route)
    {
        $this->route = $route;
        $this->resolver = $resolver;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->resolver->resolve($this->route->getHandler())
            ->process($request, $handler);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->process($request, new class implements RequestHandlerInterface
        {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                ExceptionFactory::emptyHandler();
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
    public function addPrefix(string $prefix): Contracts\Route
    {
        return $this->route->addPrefix($prefix);
    }

    /**
     * @inheritDoc
     */
    public function addSuffix(string $suffix): Contracts\Route
    {
        return $this->route->addSuffix($suffix);
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
    public function getMethods(): array
    {
        return $this->route->getMethods();
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
    public function withAttributes(array $attributes): Contracts\Route
    {
        $this->route = $this->route->withAttributes($attributes);

        return $this;
    }

}