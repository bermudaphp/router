<?php

namespace Bermuda\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface Resource
{
    public function show(ServerRequestInterface $request): ResponseInterface ;
    public function create(ServerRequestInterface $request): ResponseInterface ;
    public function update(ServerRequestInterface $request): ResponseInterface ;
    public function delete(ServerRequestInterface $request): ResponseInterface ;
    public function edit(ServerRequestInterface $request): ResponseInterface ;
    public function get(ServerRequestInterface $request): ResponseInterface ;
    public function store(ServerRequestInterface $request): ResponseInterface ;
}
