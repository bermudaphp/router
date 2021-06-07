<?php

namespace Bermuda\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface ResourceInterface
 * @package Bermuda\Router
 */
interface ResourceInterface
{
    public function show(ServerRequestInterface $request): ResponseInterface ;
    public function create(ServerRequestInterface $request): ResponseInterface ;
    public function update(ServerRequestInterface $request): ResponseInterface ;
    public function delete(ServerRequestInterface $request): ResponseInterface ;
}
