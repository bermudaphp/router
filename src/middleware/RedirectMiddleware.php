<?php


namespace Bermuda\Router\Middleware;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseFactoryInterface;


/**
 * Class RedirectMiddleware
 * @package Bermuda\Router\Middleware
 */
class RedirectMiddleware implements MiddlewareInterface
{
    private string $reTo;
    private bool $permanent;
    private ResponseFactoryInterface;
    
    public function __construct(string $path, ResponseFactoryInterface $factory, bool $permanent = false)
    {
        $this->reTo = $path;
        $this->factory = $factory;
        $this->permanent = $permanent;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->factory->createResponse($this->permanent ? 301 : 302)->withHeader('Location', $this->reTo);
    }
}
