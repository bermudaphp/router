<?php

namespace Bermuda\Router\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final class RedirectMiddleware implements MiddlewareInterface, RequestHandlerInterface
{
    private $verifier = null;
    public function __construct(
        private string $location, 
        private ResponseFactoryInterface $responseFactory,
        callable $verifier = null,
        private bool $permanent = false)
    {
        if ($verifier !== null) {
            $this->verifier = static fn(ServerRequestInterface $request): bool => $verifier($request);
        }
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->verifier !== null && ($this->verifier)($request)) {
            return $this->handle($request);
        }
        
        return $handler->handle($request);
    }
    
    public function permanent(bool $permanent = null): bool
    {
        if ($permanent !== null) {
            $old = $this->permanent;
            $this->permanent = $permanent;
            return $old;
        }
        
        return $this->permanent;
    }
    
    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->createResponse($this->permanent ? 301 : 302)
            ->withHeader('Location', $this->location);
    }
    
    /**
     * @param string $uri
     * @param callable $verifier
     * @param bool $permanent
     * @return callable
     */
    public static function lazy(string $location, callable $verifier, bool $permanent = false): callable
    {
        return static fn(ContainerInterface $container): MiddlewareInterface => 
            new RedirectMiddleware($location, $container->get(ResponseFactoryInterface::class), $verifier, $permanent);
    }
}
