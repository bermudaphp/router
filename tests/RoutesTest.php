<?php

declare(strict_types=1);

namespace Bermuda\Router\Tests;

use Bermuda\Router\Routes;
use Bermuda\Router\RouteRecord;
use Bermuda\Router\RouteGroup;
use Bermuda\Router\Compiler;
use Bermuda\Router\CompilerInterface;
use Bermuda\Router\Exception\RouterException;
use Bermuda\Router\Exception\RouteNotRegisteredException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\Group;

#[Group('routes')]
#[TestDox('Routes collection tests')]
final class RoutesTest extends TestCase
{
    private Routes $routes;
    private CompilerInterface $compiler;

    protected function setUp(): void
    {
        $this->compiler = new Compiler();
        $this->routes = new Routes($this->compiler);
    }

    #[Test]
    #[TestDox('Can construct Routes with default compiler')]
    public function can_construct_with_default_compiler(): void
    {
        $routes = new Routes();
        $this->assertInstanceOf(Routes::class, $routes);
    }

    #[Test]
    #[TestDox('Can construct Routes with custom compiler')]
    public function can_construct_with_custom_compiler(): void
    {
        $compiler = new Compiler(['id' => '\d+']);
        $routes = new Routes($compiler);
        $this->assertInstanceOf(Routes::class, $routes);
    }

    #[Test]
    #[TestDox('Can add simple route')]
    public function can_add_simple_route(): void
    {
        $route = RouteRecord::get('home', '/', 'HomeController');
        $result = $this->routes->addRoute($route);

        $this->assertSame($this->routes, $result);
        $this->assertSame($route, $this->routes->getRoute('home'));
    }

    #[Test]
    #[TestDox('Can add route with parameters')]
    public function can_add_route_with_parameters(): void
    {
        $route = RouteRecord::get('user.show', '/users/[id]', 'UserController');
        $this->routes->addRoute($route);

        $retrieved = $this->routes->getRoute('user.show');
        $this->assertSame($route, $retrieved);
        $this->assertEquals('/users/[id]', (string) $retrieved->path);
    }

    #[Test]
    #[TestDox('Throws exception when adding duplicate route name')]
    public function throws_exception_when_adding_duplicate_route_name(): void
    {
        $route1 = RouteRecord::get('duplicate', '/', 'Controller1');
        $route2 = RouteRecord::get('duplicate', '/other', 'Controller2');

        $this->routes->addRoute($route1);

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Route "duplicate" is already registered');
        $this->routes->addRoute($route2);
    }

    #[Test]
    #[TestDox('Returns null for non-existent route')]
    public function returns_null_for_non_existent_route(): void
    {
        $result = $this->routes->getRoute('nonexistent');
        $this->assertNull($result);
    }

    #[Test]
    #[TestDox('Can iterate over empty routes')]
    public function can_iterate_over_empty_routes(): void
    {
        $routes = [];
        foreach ($this->routes as $route) {
            $routes[] = $route;
        }
        $this->assertEmpty($routes);
    }

    #[Test]
    #[TestDox('Can iterate over multiple routes')]
    public function can_iterate_over_multiple_routes(): void
    {
        $route1 = RouteRecord::get('home', '/', 'HomeController');
        $route2 = RouteRecord::get('about', '/about', 'AboutController');

        $this->routes->addRoute($route1);
        $this->routes->addRoute($route2);

        $routes = [];
        foreach ($this->routes as $route) {
            $routes[] = $route;
        }

        $this->assertCount(2, $routes);
        $this->assertSame($route1, $routes[0]);
        $this->assertSame($route2, $routes[1]);
    }

    #[Test]
    #[TestDox('Can match static route')]
    public function can_match_static_route(): void
    {
        $route = RouteRecord::get('home', '/', 'HomeController');
        $this->routes->addRoute($route);

        $matched = $this->routes->match($this->routes, '/', 'GET');

        $this->assertSame($route, $matched);
    }

    #[Test]
    #[TestDox('Can match static route with query parameters')]
    public function can_match_static_route_with_query_parameters(): void
    {
        $route = RouteRecord::get('search', '/search', 'SearchController');
        $this->routes->addRoute($route);

        $matched = $this->routes->match($this->routes, '/search?q=test', 'GET');

        $this->assertSame($route, $matched);
    }

    #[Test]
    #[TestDox('Can match dynamic route with parameters')]
    public function can_match_dynamic_route_with_parameters(): void
    {
        $route = RouteRecord::get('user.show', '/users/[id]', 'UserController');
        $this->routes->addRoute($route);

        $matched = $this->routes->match($this->routes, '/users/123', 'GET');

        $this->assertNotNull($matched);
        $this->assertEquals('user.show', $matched->name);
        $this->assertEquals('123', $matched->parameters->get('id'));
    }

    #[Test]
    #[TestDox('Can match route with multiple parameters')]
    public function can_match_route_with_multiple_parameters(): void
    {
        $route = RouteRecord::get('post.show', '/posts/[year]/[month]/[slug]', 'PostController');
        $this->routes->addRoute($route);

        $matched = $this->routes->match($this->routes, '/posts/2024/12/hello-world', 'GET');

        $this->assertNotNull($matched);
        $this->assertEquals('2024', $matched->parameters->get('year'));
        $this->assertEquals('12', $matched->parameters->get('month'));
        $this->assertEquals('hello-world', $matched->parameters->get('slug'));
    }

    #[Test]
    #[TestDox('Can match route with optional parameters')]
    public function can_match_route_with_optional_parameters(): void
    {
        $route = RouteRecord::get('posts.list', '/posts/[?category]', 'PostController');
        $this->routes->addRoute($route);

        // Match without optional parameter
        $matched1 = $this->routes->match($this->routes, '/posts', 'GET');
        $this->assertNotNull($matched1);
        $this->assertEquals('posts.list', $matched1->name);

        // Match with optional parameter
        $matched2 = $this->routes->match($this->routes, '/posts/tech', 'GET');
        $this->assertNotNull($matched2);
        $this->assertEquals('tech', $matched2->parameters->get('category'));
    }

    #[Test]
    #[TestDox('Returns null for non-matching route')]
    public function returns_null_for_non_matching_route(): void
    {
        $route = RouteRecord::get('home', '/', 'HomeController');
        $this->routes->addRoute($route);

        $matched = $this->routes->match($this->routes, '/nonexistent', 'GET');

        $this->assertNull($matched);
    }

    #[Test]
    #[TestDox('Returns null for wrong HTTP method')]
    public function returns_null_for_wrong_http_method(): void
    {
        $route = RouteRecord::get('home', '/', 'HomeController')->withMethods(['GET']);
        $this->routes->addRoute($route);

        $matched = $this->routes->match($this->routes, '/', 'POST');

        $this->assertNull($matched);
    }

    #[Test]
    #[DataProvider('httpMethodProvider')]
    #[TestDox('Can match routes with various HTTP methods')]
    public function can_match_routes_with_various_http_methods(string $method): void
    {
        $route = RouteRecord::any('api.endpoint', '/api/data', 'ApiController', [$method]);
        $this->routes->addRoute($route);

        $matched = $this->routes->match($this->routes, '/api/data', $method);

        $this->assertNotNull($matched);
        $this->assertEquals('api.endpoint', $matched->name);
    }

    public static function httpMethodProvider(): array
    {
        return [
            ['GET'],
            ['POST'],
            ['PUT'],
            ['PATCH'],
            ['DELETE'],
            ['HEAD'],
            ['OPTIONS'],
        ];
    }

    #[Test]
    #[TestDox('Can generate URL for static route')]
    public function can_generate_url_for_static_route(): void
    {
        $route = RouteRecord::get('home', '/', 'HomeController');
        $this->routes->addRoute($route);

        $url = $this->routes->generate($this->routes, 'home');

        $this->assertEquals('/', $url);
    }

    #[Test]
    #[TestDox('Can generate URL for route with parameters')]
    public function can_generate_url_for_route_with_parameters(): void
    {
        $route = RouteRecord::get('user.show', '/users/[id]', 'UserController');
        $this->routes->addRoute($route);

        $url = $this->routes->generate($this->routes, 'user.show', ['id' => 123]);

        $this->assertEquals('/users/123', $url);
    }

    #[Test]
    #[TestDox('Can generate URL for route with optional parameters')]
    public function can_generate_url_for_route_with_optional_parameters(): void
    {
        $route = RouteRecord::get('posts.list', '/posts/[?category]', 'PostController');
        $this->routes->addRoute($route);

        // Without optional parameter
        $url1 = $this->routes->generate($this->routes, 'posts.list');
        $this->assertEquals('/posts', $url1);

        // With optional parameter
        $url2 = $this->routes->generate($this->routes, 'posts.list', ['category' => 'tech']);
        $this->assertEquals('/posts/tech', $url2);
    }

    #[Test]
    #[TestDox('Throws exception when generating URL for non-existent route')]
    public function throws_exception_when_generating_url_for_non_existent_route(): void
    {
        $this->expectException(RouteNotRegisteredException::class);
        $this->expectExceptionMessage('Route "nonexistent" is not registered');

        $this->routes->generate($this->routes, 'nonexistent');
    }

    #[Test]
    #[TestDox('Can create route group')]
    public function can_create_route_group(): void
    {
        $group = $this->routes->group('api', '/api/v1');

        $this->assertInstanceOf(RouteGroup::class, $group);
        $this->assertEquals('api', $group->name);
        $this->assertEquals('/api/v1', $group->pathPrefix);
    }

    #[Test]
    #[TestDox('Can retrieve existing route group')]
    public function can_retrieve_existing_route_group(): void
    {
        $group1 = $this->routes->group('api', '/api/v1');
        $group2 = $this->routes->group('api');

        $this->assertSame($group1, $group2);
    }

    #[Test]
    #[TestDox('Throws exception when retrieving non-existent group without prefix')]
    public function throws_exception_when_retrieving_non_existent_group_without_prefix(): void
    {
        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Group "nonexistent" not found');

        $this->routes->group('nonexistent');
    }

    #[Test]
    #[TestDox('Can add routes through group')]
    public function can_add_routes_through_group(): void
    {
        $group = $this->routes->group('api', '/api/v1');
        $group->get('users', '/users', 'UserController');

        $route = $this->routes->getRoute('api.users');

        $this->assertNotNull($route);
        $this->assertEquals('api.users', $route->name);
        $this->assertEquals('/api/v1/users', (string) $route->path);
    }

    #[Test]
    #[TestDox('Can convert routes to array')]
    public function can_convert_routes_to_array(): void
    {
        $staticRoute = RouteRecord::get('home', '/', 'HomeController');
        $dynamicRoute = RouteRecord::get('user.show', '/users/[id]', 'UserController');

        $this->routes->addRoute($staticRoute);
        $this->routes->addRoute($dynamicRoute);

        $array = $this->routes->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('static', $array);
        $this->assertArrayHasKey('dynamic', $array);
        $this->assertCount(1, $array['static']);
        $this->assertCount(1, $array['dynamic']);

        // Check static route data
        $staticData = $array['static'][0];
        $this->assertEquals('home', $staticData['name']);
        $this->assertEquals('/', $staticData['path']);
        $this->assertEquals(['GET'], $staticData['methods']);

        // Check dynamic route data
        $dynamicData = $array['dynamic'][0];
        $this->assertEquals('user.show', $dynamicData['name']);
        $this->assertEquals('/users/[id]', $dynamicData['path']);
        $this->assertNotEmpty($dynamicData['regex']);
        $this->assertEquals(['id'], $dynamicData['parameters']);
    }

    #[Test]
    #[TestDox('Can clone routes collection')]
    public function can_clone_routes_collection(): void
    {
        $route = RouteRecord::get('home', '/', 'HomeController');
        $this->routes->addRoute($route);
        $this->routes->group('api', '/api/v1');

        $cloned = clone $this->routes;

        // Original and clone should have same routes but be different objects
        $this->assertNotSame($this->routes, $cloned);
        $this->assertEquals($this->routes->getRoute('home')->name, $cloned->getRoute('home')->name);
        $this->assertNotSame($this->routes->getRoute('home'), $cloned->getRoute('home'));
    }

    #[Test]
    #[TestDox('Routes maintain insertion order during iteration')]
    public function routes_maintain_insertion_order_during_iteration(): void
    {
        $routes = [
            RouteRecord::get('first', '/first', 'FirstController'),
            RouteRecord::get('second', '/second', 'SecondController'),
            RouteRecord::get('third', '/third', 'ThirdController'),
        ];

        foreach ($routes as $route) {
            $this->routes->addRoute($route);
        }

        $iterated = [];
        foreach ($this->routes as $route) {
            $iterated[] = $route->name;
        }

        $this->assertEquals(['first', 'second', 'third'], $iterated);
    }

    #[Test]
    #[TestDox('Can handle routes with same path but different methods')]
    public function can_handle_routes_with_same_path_but_different_methods(): void
    {
        $getRoute = RouteRecord::get('api.get', '/api/data', 'GetController');
        $postRoute = RouteRecord::post('api.post', '/api/data', 'PostController');

        $this->routes->addRoute($getRoute);
        $this->routes->addRoute($postRoute);

        $getMatched = $this->routes->match($this->routes, '/api/data', 'GET');
        $postMatched = $this->routes->match($this->routes, '/api/data', 'POST');

        $this->assertEquals('api.get', $getMatched->name);
        $this->assertEquals('api.post', $postMatched->name);
    }

    #[Test]
    #[TestDox('Can match route with normalized path')]
    public function can_match_route_with_normalized_path(): void
    {
        $route = RouteRecord::get('test', '/test/path', 'TestController');
        $this->routes->addRoute($route);

        // Test various path formats that should normalize to the same thing
        $testPaths = [
            '/test/path',
            '/test//path',
            '/test/path/',
            '//test/path',
            '/test/path?query=value',
            '/test/path#fragment',
            '/test\\path',  // backslashes should be normalized
        ];

        foreach ($testPaths as $path) {
            $matched = $this->routes->match($this->routes, $path, 'GET');
            $this->assertNotNull($matched, "Failed to match path: $path");
            $this->assertEquals('test', $matched->name);
        }
    }

    #[Test]
    #[TestDox('Route parameters are converted to appropriate types')]
    public function route_parameters_are_converted_to_appropriate_types(): void
    {
        $route = RouteRecord::get('test', '/test/[id]/[price]/[active]', 'TestController');
        $this->routes->addRoute($route);

        $matched = $this->routes->match($this->routes, '/test/123/45.67/1', 'GET');

        $this->assertNotNull($matched);

        $this->assertSame(123, $matched->parameters->get('id'), 'Integer strings should be converted to integers');
        $this->assertSame(45.67, $matched->parameters->get('price'), 'Float strings should be converted to floats');
        $this->assertSame(1, $matched->parameters->get('active'), 'Numeric strings should be converted to numbers');

        // Проверяем типы
        $this->assertIsInt($matched->parameters->get('id'));
        $this->assertIsFloat($matched->parameters->get('price'));
        $this->assertIsInt($matched->parameters->get('active'));
    }

    #[Test]
    #[TestDox('Non-numeric parameters remain as strings')]
    public function non_numeric_parameters_remain_as_strings(): void
    {
        $route = RouteRecord::get('test', '/test/[name]/[code]', 'TestController');
        $this->routes->addRoute($route);

        $matched = $this->routes->match($this->routes, '/test/john-doe/abc123def', 'GET');

        $this->assertNotNull($matched);

        // Не-числовые строки остаются строками
        $this->assertSame('john-doe', $matched->parameters->get('name'));
        $this->assertSame('abc123def', $matched->parameters->get('code'));

        $this->assertIsString($matched->parameters->get('name'));
        $this->assertIsString($matched->parameters->get('code'));
    }
}