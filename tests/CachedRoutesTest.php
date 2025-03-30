<?php

namespace Bermuda\Router\Tests;

use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertNull;

class CachedRoutesTest extends RoutesTest
{
    protected function setUp(): void
    {
        $this->routes = Routes::createFromArray([
            'dynamic' => [
                0 => [
                    'name' => 'users.get',
                    'path' => '/api/v1/users/[?id:\d+]',
                    'methods' => [
                        0 => 'GET',
                    ],
                    'handler' => [
                        0 => '',
                    ],
                    'regexp' => '#^/api/v1/users(/\d+)?/?$#',
                ],
                1 => [
                    'name' => 'posts.get',
                    'path' => '/api/v1/posts/[id:\d+]',
                    'methods' => [
                        0 => 'GET',
                    ],
                    'handler' => [
                        0 => '',
                    ],
                    'regexp' => '#^/api/v1/posts/\d+/?$#',
                ],
                2 => [
                    'name' => 'videos.get',
                    'path' => '/api/v1/videos/[?id:\d+]',
                    'methods' => [
                        0 => 'GET',
                    ],
                    'handler' => [
                        0 => '',
                    ],
                    'regexp' => '#^/api/v1/videos(/\d+)?/?$#',
                ],
            ],
            'static' => [
                '/index' => [
                    'name' => 'index',
                    'path' => '/index',
                    'methods' => [
                        0 => 'GET',
                    ],
                    'handler' => [
                        0 => '',
                    ],
                    'regexp' => '#^/index/?$#',
                ],
            ],
        ]);
    }

    public function testMatchedRouteWithoutTokens(): void
    {
        $route = $this->routes->match($this->routes,'/index', 'GET');
        assertNotNull($route);
    }

    public function testNotMatchedRouteWithoutTokens(): void
    {
        $route = $this->routes->match($this->routes,'/index', 'POST');
        assertNull($route);
    }
}
