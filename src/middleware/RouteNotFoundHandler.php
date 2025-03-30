<?php

namespace Bermuda\Router\Middleware;

use Bermuda\Router\Exception\RouteNotFoundException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RouteNotFoundHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly bool $exceptionMode = false
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->exceptionMode) {
            throw new RouteNotFoundException((string)$request->getUri(), $request->getMethod());
        }

        $response = $this->responseFactory->createResponse(404);
        $response->getBody()->write(json_encode(['message' => 'Endpoint not found.']));
        
        return $response;
    }
}
