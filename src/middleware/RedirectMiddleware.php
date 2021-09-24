<?php

namespace Bermuda\Router\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final class RedirectMiddleware implements MiddlewareInterface, RequestHandlerInterface
{
    private $verifier;
    public function __construct(
        private string $location, 
        private ResponseFactoryInterface $responseFactory,
        callable $verifier,
        private bool $permanent = false)
    {
        $this->$verifier = static fn(ServerRequestInterface $request, ... $arguments) use ($verifier): bool {
            return $verifier($request, ... $arguments);
        };
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
