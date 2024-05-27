<?php

namespace Bermuda\Router\Tests;

use Bermuda\Router\Exception\GeneratorException;
use Bermuda\Router\Exception\RouterException;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertNull;

class RoutesTest extends TestCase
{
    protected Routes $routes;

    protected function setUp(): void
    {
        $this->routes = new Routes();
        $group = $this->routes->group('api', '/api/v1');

        $group->addRoute(RouteRecord::get('users.get', '/users/[?id]', ''));
        $group->addRoute(RouteRecord::get('posts.get', '/posts/[id]', ''));
        $group->addRoute(RouteRecord::get('videos.get', '/videos/[?id]', ''));
        $group->addRoute(RouteRecord::post('users.create', '/users', ''));
    }

    public function testMatchedRouteWithoutTokens(): void
    {
        $route = $this->routes->match($this->routes, '/api/v1/users', 'POST');
        assertNotNull($route);
    }

    public function testMatchedRouteWithTokens(): void
    {
        $route = $this->routes->match($this->routes,'/api/v1/users/10', 'GET');
        assertNotNull($route);
    }

    public function testMatchedRouteWithOptionalTokens(): void
    {
        $route = $this->routes->match($this->routes,'/api/v1/users', 'GET');
        assertNotNull($route);
    }

    public function testNotMatchedRoute(): void
    {
        $route = $this->routes->match($this->routes,'/api/v1/users/id', 'GET');
        assertNull($route);
    }

    public function testNotMatchedRouteForMethod(): void
    {
        $route = $this->routes->match($this->routes,'/api/v1/users/10', 'DELETE');
        assertNull($route);
    }

    public function testGenerateUriSuccessfully()
    {
        $uri = $this->routes->generate($this->routes, 'users.get', ['id' => '15']);
        self::assertTrue('/api/v1/users/15' === $uri);
    }

    public function testGenerateUriRequiredParamMissing()
    {
        $this->expectException(GeneratorException::class);
        $this->routes->generate($this->routes, 'posts.get');

    }

    public function testGenerateUriRouteNotFound()
    {
        $this->expectException(RouterException::class);
        $this->routes->generate($this->routes, 'users.update');

    }
}
