<?php


namespace Lobster\Routing;


use Lobster\Http\Contracts\ErrorResponseGenerator;
use Lobster\Routing\Exceptions\MethodNotAllowedException;
use Lobster\Routing\Exceptions\RouteNotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


/**
 * Class RouteExceptionHandler
 * @package Lobster\Routing
 */
class RouteExceptionHandler implements MiddlewareInterface
{
    private ErrorResponseGenerator $generator;

    /**
     * RouteExceptionHandler constructor.
     * @param ErrorResponseGenerator $generator
     */
    public function __construct(ErrorResponseGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try
        {
            $response = $handler->handle($request);
        } catch (RouteNotFoundException|MethodNotAllowedException $e)
        {
            $response = ($this->generator)($e, $request);
        }

        return $response;
    }
}