<?php

namespace Bermuda\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class Resource
{
    abstract public function show(ServerRequestInterface $request): ResponseInterface ;
    abstract public function create(ServerRequestInterface $request): ResponseInterface ;
    abstract public function update(ServerRequestInterface $request): ResponseInterface ;
    abstract public function delete(ServerRequestInterface $request): ResponseInterface ;
    abstract public function edit(ServerRequestInterface $request): ResponseInterface ;
    abstract public function get(ServerRequestInterface $request): ResponseInterface ;
    abstract public function store(ServerRequestInterface $request): ResponseInterface ;
    
    abstract public static function register(RouteMap $routes): RouteMap ;
}
