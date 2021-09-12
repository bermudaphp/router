<?php

namespace Bermuda\Router\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final class RedirectMiddleware implements MiddlewareInterface, RequestHandlerInterface
{
    private string $reTo;
    private bool $permanent;
    private ResponseFactoryInterface $factory;
    
    public function __construct(string $uri, ResponseFactoryInterface $factory, bool $permanent = false)
    {
        $this->reTo = $uri;
        $this->factory = $factory;
        $this->permanent = $permanent;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->handle($request);
    }
    
    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->factory->createResponse($this->permanent ? 301 : 302)->withHeader('Location', $this->reTo);
    }
    
    /**
     * @param string $uri
     * @param bool $permanent
     * @return callable
     */
    public static function lazy(string $uri, bool $permanent = false): callable
    {
        return static fn(ContainerInterface $container): MiddlewareInterface => 
            new RedirectMiddleware($uri, $container->get(ResponseFactoryInterface::class), $permanent);
    }
}
