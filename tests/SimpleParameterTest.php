<?php

declare(strict_types=1);

namespace Bermuda\Router\Tests;

use Bermuda\Router\Routes;
use Bermuda\Router\RoutesCache;
use Bermuda\Router\RouteRecord;
use Bermuda\Router\Compiler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\Group;

#[Group('simple-parameters')]
#[TestDox('Corrected parameter extraction tests')]
final class SimpleParameterTest extends TestCase
{
    #[Test]
    #[TestDox('Routes converts numeric parameters to numbers')]
    public function routes_converts_numeric_parameters_to_numbers(): void
    {
        $routes = new Routes(new Compiler());
        $route = RouteRecord::get('user.show', '/users/[id]', 'UserController');
        $routes->addRoute($route);

        $matched = $routes->match($routes, '/users/123', 'GET');

        $this->assertNotNull($matched);
        $this->assertEquals('user.show', $matched->name);

        $id = $matched->parameters->get('id');
        $this->assertSame(123, $id, 'Routes should convert numeric strings to integers');
        $this->assertIsInt($id, 'Numeric parameter should be integer type');
    }

    #[Test]
    #[TestDox('RoutesCache converts numeric parameters as expected')]
    public function routes_cache_converts_numeric_parameters_as_expected(): void
    {
        $cacheData = [
            'static' => [],
            'dynamic' => [
                [
                    'name' => 'user.show',
                    'path' => '/users/[id]',
                    'methods' => ['GET'],
                    'regex' => '/^\/users\/(?P<id>[^\/]+)$/',
                    'handler' => 'UserController',
                    'middleware' => [],
                    'group' => null,
                    'parameters' => ['id'],
                    'defaults' => null,
                ]
            ]
        ];

        $routesCache = new RoutesCache($cacheData, new Compiler());

        $matched = $routesCache->match($routesCache, '/users/123', 'GET');

        $this->assertNotNull($matched);
        $this->assertEquals('user.show', $matched->name);

        // RoutesCache конвертирует числовые строки в числа
        $id = $matched->parameters->get('id');
        $this->assertSame(123, $id, 'RoutesCache should convert numeric strings to integers');
        $this->assertIsInt($id, 'Numeric parameter should be integer type');
    }

    #[Test]
    #[TestDox('Both implementations should behave identically')]
    public function both_implementations_should_behave_identically(): void
    {
        // Setup Routes
        $routes = new Routes(new Compiler());
        $routes->addRoute(RouteRecord::get('product.show', '/products/[id]/[price]', 'ProductController'));

        // Setup RoutesCache
        $cacheData = [
            'static' => [],
            'dynamic' => [
                [
                    'name' => 'product.show',
                    'path' => '/products/[id]/[price]',
                    'methods' => ['GET'],
                    'regex' => '/^\/products\/(?P<id>[^\/]+)\/(?P<price>[^\/]+)$/',
                    'handler' => 'ProductController',
                    'middleware' => [],
                    'group' => null,
                    'parameters' => ['id', 'price'],
                    'defaults' => null,
                ]
            ]
        ];
        $routesCache = new RoutesCache($cacheData, new Compiler());

        // Test integer and float parameters
        $routesMatch = $routes->match($routes, '/products/456/99.99', 'GET');
        $cacheMatch = $routesCache->match($routesCache, '/products/456/99.99', 'GET');

        $this->assertNotNull($routesMatch);
        $this->assertNotNull($cacheMatch);

        // Routes parameters
        $routesId = $routesMatch->parameters->get('id');
        $routesPrice = $routesMatch->parameters->get('price');

        // RoutesCache parameters
        $cacheId = $cacheMatch->parameters->get('id');
        $cachePrice = $cacheMatch->parameters->get('price');

        $this->assertIsInt($routesId, 'Routes should convert integer strings to int');
        $this->assertIsFloat($routesPrice, 'Routes should convert float strings to float');
        $this->assertIsInt($cacheId, 'RoutesCache should convert integer strings to int');
        $this->assertIsFloat($cachePrice, 'RoutesCache should convert float strings to float');

        // Values should be identical between implementations
        $this->assertSame($routesId, $cacheId, 'ID should be identical between implementations');
        $this->assertSame($routesPrice, $cachePrice, 'Price should be identical between implementations');

        // Specific value assertions
        $this->assertSame(456, $routesId);
        $this->assertSame(99.99, $routesPrice);
        $this->assertSame(456, $cacheId);
        $this->assertSame(99.99, $cachePrice);
    }

    #[Test]
    #[TestDox('String parameters remain strings in both implementations')]
    public function string_parameters_remain_strings_in_both_implementations(): void
    {
        // Setup Routes
        $routes = new Routes(new Compiler());
        $routes->addRoute(RouteRecord::get('user.profile', '/users/[username]', 'UserController'));

        // Setup RoutesCache
        $cacheData = [
            'static' => [],
            'dynamic' => [
                [
                    'name' => 'user.profile',
                    'path' => '/users/[username]',
                    'methods' => ['GET'],
                    'regex' => '/^\/users\/(?P<username>[^\/]+)$/',
                    'handler' => 'UserController',
                    'middleware' => [],
                    'group' => null,
                    'parameters' => ['username'],
                    'defaults' => null,
                ]
            ]
        ];
        $routesCache = new RoutesCache($cacheData, new Compiler());

        $routesMatch = $routes->match($routes, '/users/john-doe', 'GET');
        $cacheMatch = $routesCache->match($routesCache, '/users/john-doe', 'GET');

        $this->assertNotNull($routesMatch);
        $this->assertNotNull($cacheMatch);

        $routesUsername = $routesMatch->parameters->get('username');
        $cacheUsername = $cacheMatch->parameters->get('username');

        // Both should be strings and identical
        $this->assertIsString($routesUsername, 'Non-numeric values should remain strings in Routes');
        $this->assertIsString($cacheUsername, 'Non-numeric values should remain strings in RoutesCache');
        $this->assertSame('john-doe', $routesUsername);
        $this->assertSame('john-doe', $cacheUsername);
        $this->assertSame($routesUsername, $cacheUsername, 'String values should be identical between implementations');
    }

    #[Test]
    #[TestDox('Default values work consistently in both implementations')]
    public function default_values_work_consistently_in_both_implementations(): void
    {
        // Setup Routes with defaults
        $routes = new Routes(new Compiler());
        $routes->addRoute(RouteRecord::get('posts.list', '/posts/[?category]', 'PostController')
            ->withDefaults(['category' => 'general', 'per_page' => 10]));

        // Setup RoutesCache with defaults
        $cacheData = [
            'static' => [],
            'dynamic' => [
                [
                    'name' => 'posts.list',
                    'path' => '/posts/[?category]',
                    'methods' => ['GET'],
                    'regex' => '/^\/posts(?:\/(?P<category>[^\/]+))?$/',
                    'handler' => 'PostController',
                    'middleware' => [],
                    'group' => null,
                    'parameters' => ['category'],
                    'defaults' => ['category' => 'general', 'per_page' => 10],
                ]
            ]
        ];
        $routesCache = new RoutesCache($cacheData, new Compiler());

        // Test without optional parameter (should use defaults)
        $routesMatch = $routes->match($routes, '/posts', 'GET');
        $cacheMatch = $routesCache->match($routesCache, '/posts', 'GET');

        $this->assertNotNull($routesMatch);
        $this->assertNotNull($cacheMatch);

        $routesCategory = $routesMatch->parameters->get('category');
        $routesPerPage = $routesMatch->parameters->get('per_page');
        $cacheCategory = $cacheMatch->parameters->get('category');
        $cachePerPage = $cacheMatch->parameters->get('per_page');

        // Both should have the same default values with correct types
        $this->assertEquals('general', $routesCategory);
        $this->assertEquals('general', $cacheCategory);
        $this->assertEquals(null, $routesPerPage);
        $this->assertEquals(null, $cachePerPage);

        // Values should be identical between implementations
        $this->assertSame($routesCategory, $cacheCategory);
        $this->assertSame($routesPerPage, $cachePerPage);

        // Test with optional parameter provided
        $routesMatch2 = $routes->match($routes, '/posts/technology', 'GET');
        $cacheMatch2 = $routesCache->match($routesCache, '/posts/technology', 'GET');

        $this->assertNotNull($routesMatch2);
        $this->assertNotNull($cacheMatch2);

        $routesCategory2 = $routesMatch2->parameters->get('category');
        $cacheCategory2 = $cacheMatch2->parameters->get('category');

        // Both should use the provided parameter and be identical
        $this->assertEquals('technology', $routesCategory2);
        $this->assertEquals('technology', $cacheCategory2);
        $this->assertSame($routesCategory2, $cacheCategory2);
    }

    #[Test]
    #[TestDox('Zero values are handled correctly by both implementations')]
    public function zero_values_are_handled_correctly_by_both_implementations(): void
    {
        // Setup Routes
        $routes = new Routes(new Compiler());
        $routes->addRoute(RouteRecord::get('test.zero', '/test/[int_zero]/[float_zero]', 'TestController'));

        // Setup RoutesCache
        $cacheData = [
            'static' => [],
            'dynamic' => [
                [
                    'name' => 'test.zero',
                    'path' => '/test/[int_zero]/[float_zero]',
                    'methods' => ['GET'],
                    'regex' => '/^\/test\/(?P<int_zero>[^\/]+)\/(?P<float_zero>[^\/]+)$/',
                    'handler' => 'TestController',
                    'middleware' => [],
                    'group' => null,
                    'parameters' => ['int_zero', 'float_zero'],
                    'defaults' => null,
                ]
            ]
        ];
        $routesCache = new RoutesCache($cacheData, new Compiler());

        $routesMatch = $routes->match($routes, '/test/0/0.0', 'GET');
        $cacheMatch = $routesCache->match($routesCache, '/test/0/0.0', 'GET');

        $this->assertNotNull($routesMatch);
        $this->assertNotNull($cacheMatch);

        $routesIntZero = $routesMatch->parameters->get('int_zero');
        $routesFloatZero = $routesMatch->parameters->get('float_zero');
        $cacheIntZero = $cacheMatch->parameters->get('int_zero');
        $cacheFloatZero = $cacheMatch->parameters->get('float_zero');

        // Both should handle zero values correctly
        $this->assertSame(0, $routesIntZero, 'Routes should convert "0" to integer 0');
        $this->assertSame(0.0, $routesFloatZero, 'Routes should convert "0.0" to float 0.0');
        $this->assertSame(0, $cacheIntZero, 'RoutesCache should convert "0" to integer 0');
        $this->assertSame(0.0, $cacheFloatZero, 'RoutesCache should convert "0.0" to float 0.0');

        $this->assertIsInt($routesIntZero);
        $this->assertIsFloat($routesFloatZero);
        $this->assertIsInt($cacheIntZero);
        $this->assertIsFloat($cacheFloatZero);

        // Values should be identical between implementations
        $this->assertSame($routesIntZero, $cacheIntZero);
        $this->assertSame($routesFloatZero, $cacheFloatZero);
    }

    #[Test]
    #[TestDox('Mixed alphanumeric parameters remain as strings')]
    public function mixed_alphanumeric_parameters_remain_as_strings(): void
    {
        // Setup Routes
        $routes = new Routes(new Compiler());
        $routes->addRoute(RouteRecord::get('mixed.test', '/mixed/[code1]/[code2]', 'MixedController'));

        // Setup RoutesCache
        $cacheData = [
            'static' => [],
            'dynamic' => [
                [
                    'name' => 'mixed.test',
                    'path' => '/mixed/[code1]/[code2]',
                    'methods' => ['GET'],
                    'regex' => '/^\/mixed\/(?P<code1>[^\/]+)\/(?P<code2>[^\/]+)$/',
                    'handler' => 'MixedController',
                    'middleware' => [],
                    'group' => null,
                    'parameters' => ['code1', 'code2'],
                    'defaults' => null,
                ]
            ]
        ];
        $routesCache = new RoutesCache($cacheData, new Compiler());

        $routesMatch = $routes->match($routes, '/mixed/123abc/abc123', 'GET');
        $cacheMatch = $routesCache->match($routesCache, '/mixed/123abc/abc123', 'GET');

        $this->assertNotNull($routesMatch);
        $this->assertNotNull($cacheMatch);

        $routesCode1 = $routesMatch->parameters->get('code1');
        $routesCode2 = $routesMatch->parameters->get('code2');
        $cacheCode1 = $cacheMatch->parameters->get('code1');
        $cacheCode2 = $cacheMatch->parameters->get('code2');

        // Mixed alphanumeric should remain as strings
        $this->assertSame('123abc', $routesCode1);
        $this->assertSame('abc123', $routesCode2);
        $this->assertSame('123abc', $cacheCode1);
        $this->assertSame('abc123', $cacheCode2);

        $this->assertIsString($routesCode1);
        $this->assertIsString($routesCode2);
        $this->assertIsString($cacheCode1);
        $this->assertIsString($cacheCode2);

        // Values should be identical between implementations
        $this->assertSame($routesCode1, $cacheCode1);
        $this->assertSame($routesCode2, $cacheCode2);
    }
}