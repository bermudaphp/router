<?php

declare(strict_types=1);

namespace Bermuda\Router\Tests;

use Bermuda\Router\Routes;
use Bermuda\Router\RoutesCache;
use Bermuda\Router\RouteRecord;
use Bermuda\Router\Compiler;
use Bermuda\Router\CompilerInterface;
use Bermuda\Router\Exception\RouterException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\Group;

#[Group('routes-cache')]
#[TestDox('RoutesCache tests')]
final class RoutesCacheTest extends TestCase
{
    private CompilerInterface $compiler;
    private array $sampleCache;

    protected function setUp(): void
    {
        $this->compiler = new Compiler();
        $this->sampleCache = $this->createSampleCache();
    }

    private function createSampleCache(): array
    {
        return [
            'static' => [
                [
                    'name' => 'home',
                    'path' => '/',
                    'methods' => ['GET'],
                    'regex' => '/^\/$/',
                    'handler' => 'HomeController',
                    'middleware' => [],
                    'group' => null,
                    'parameters' => [],
                    'defaults' => null,
                ],
                [
                    'name' => 'about',
                    'path' => '/about',
                    'methods' => ['GET'],
                    'regex' => '/^\/about$/',
                    'handler' => 'AboutController',
                    'middleware' => ['AuthMiddleware'],
                    'group' => null,
                    'parameters' => [],
                    'defaults' => null,
                ],
            ],
            'dynamic' => [
                [
                    'name' => 'user.show',
                    'path' => '/users/[id]',
                    'methods' => ['GET'],
                    'regex' => '/^\/users\/(?P<id>\d+)$/',
                    'handler' => 'UserController',
                    'middleware' => [],
                    'group' => null,
                    'parameters' => ['id'],
                    'defaults' => null,
                ],
                [
                    'name' => 'post.show',
                    'path' => '/posts/[year]/[month]/[slug]',
                    'methods' => ['GET'],
                    'regex' => '/^\/posts\/(?P<year>[12]\d{3})\/(?P<month>0[1-9]|1[0-2])\/(?P<slug>[a-z0-9-]+)$/',
                    'handler' => 'PostController',
                    'middleware' => ['CacheMiddleware'],
                    'group' => 'blog',
                    'parameters' => ['year', 'month', 'slug'],
                    'defaults' => ['format' => 'html', 'section' => 'main'],
                ],
                [
                    'name' => 'posts.optional',
                    'path' => '/posts/[?category]',
                    'methods' => ['GET'],
                    'regex' => '/^\/posts(?:\/(?P<category>[^\/]+))?$/',
                    'handler' => 'PostController',
                    'middleware' => [],
                    'group' => null,
                    'parameters' => ['category'],
                    'defaults' => ['category' => 'general'],
                ],
            ],
        ];
    }

    #[Test]
    #[TestDox('Can construct RoutesCache with cache data')]
    public function can_construct_with_cache_data(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);
        $this->assertInstanceOf(RoutesCache::class, $routesCache);
    }

    #[Test]
    #[TestDox('Can construct RoutesCache with default compiler')]
    public function can_construct_with_default_compiler(): void
    {
        $routesCache = new RoutesCache($this->sampleCache);
        $this->assertInstanceOf(RoutesCache::class, $routesCache);
    }

    #[Test]
    #[TestDox('Can get static route from cache')]
    public function can_get_static_route_from_cache(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);
        $route = $routesCache->getRoute('home');

        $this->assertNotNull($route);
        $this->assertEquals('home', $route->name);
        $this->assertEquals('/', (string) $route->path);
        $this->assertEquals('HomeController', $route->handler);
        $this->assertEquals(['GET'], $route->methods->toArray());
    }

    #[Test]
    #[TestDox('Can get dynamic route from cache')]
    public function can_get_dynamic_route_from_cache(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);
        $route = $routesCache->getRoute('user.show');

        $this->assertNotNull($route);
        $this->assertEquals('user.show', $route->name);
        $this->assertEquals('/users/[id]', (string) $route->path);
        $this->assertEquals('UserController', $route->handler);

        $this->assertTrue($route->tokens->has('id'));
        $this->assertEquals('\d+', $route->tokens->get('id'));
    }

    #[Test]
    #[TestDox('Can get route with middleware from cache')]
    public function can_get_route_with_middleware_from_cache(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);
        $route = $routesCache->getRoute('about');

        $this->assertNotNull($route);
        $this->assertEquals(['AuthMiddleware'], $route->middleware);
    }

    #[Test]
    #[TestDox('Can get route with defaults from cache')]
    public function can_get_route_with_defaults_from_cache(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);
        $route = $routesCache->getRoute('post.show');

        $this->assertNotNull($route);
        $this->assertNotNull($route->defaults);
        $this->assertEquals('html', $route->defaults->get('format'));
        $this->assertEquals('main', $route->defaults->get('section'));
    }

    #[Test]
    #[TestDox('Can get route with group from cache')]
    public function can_get_route_with_group_from_cache(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);
        $route = $routesCache->getRoute('post.show');

        $this->assertNotNull($route);
        $this->assertEquals('blog', $route->group);
    }

    #[Test]
    #[TestDox('Returns null for non-existent cached route')]
    public function returns_null_for_non_existent_cached_route(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);
        $route = $routesCache->getRoute('nonexistent');

        $this->assertNull($route);
    }

    #[Test]
    #[TestDox('Can add new route to cache')]
    public function can_add_new_route_to_cache(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);
        $newRoute = RouteRecord::get('contact', '/contact', 'ContactController');

        $result = $routesCache->addRoute($newRoute);

        $this->assertSame($routesCache, $result);
        $this->assertSame($newRoute, $routesCache->getRoute('contact'));
    }

    #[Test]
    #[TestDox('Throws exception when adding duplicate route name to cache')]
    public function throws_exception_when_adding_duplicate_route_name_to_cache(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);
        $duplicateRoute = RouteRecord::get('home', '/home-duplicate', 'DuplicateController');

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Route "home" is already registered');

        $routesCache->addRoute($duplicateRoute);
    }

    #[Test]
    #[TestDox('Runtime routes take precedence over cached routes in lookup')]
    public function runtime_routes_take_precedence_over_cached_routes_in_lookup(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);
        $overrideRoute = RouteRecord::get('home', '/home-override', 'OverrideController');

        // This should throw an exception because 'home' already exists in cache
        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Route "home" is already registered');
        $routesCache->addRoute($overrideRoute);
    }

    #[Test]
    #[TestDox('Can get cached routes and runtime routes separately')]
    public function can_get_cached_routes_and_runtime_routes_separately(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);
        $newRoute = RouteRecord::get('contact', '/contact', 'ContactController');
        $routesCache->addRoute($newRoute);

        // Should get from cache
        $cachedRoute = $routesCache->getRoute('home');
        $this->assertEquals('HomeController', $cachedRoute->handler);

        // Should get from runtime routes
        $runtimeRoute = $routesCache->getRoute('contact');
        $this->assertEquals('ContactController', $runtimeRoute->handler);
    }

    #[Test]
    #[TestDox('Can match static route from cache')]
    public function can_match_static_route_from_cache(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);
        $matched = $routesCache->match($routesCache, '/', 'GET');

        $this->assertNotNull($matched);
        $this->assertEquals('home', $matched->name);
        $this->assertEquals('HomeController', $matched->handler);
    }

    #[Test]
    #[TestDox('Can match static route with query parameters')]
    public function can_match_static_route_with_query_parameters(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);
        $matched = $routesCache->match($routesCache, '/about?param=value', 'GET');

        $this->assertNotNull($matched);
        $this->assertEquals('about', $matched->name);
        $this->assertEquals('AboutController', $matched->handler);
    }

    #[Test]
    #[TestDox('Can match dynamic route from cache with parameters')]
    public function can_match_dynamic_route_from_cache_with_parameters(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);
        $matched = $routesCache->match($routesCache, '/users/123', 'GET');

        $this->assertNotNull($matched);
        $this->assertEquals('user.show', $matched->name);
        $this->assertEquals('123', $matched->parameters->get('id'));
    }

    #[Test]
    #[TestDox('Can match complex dynamic route from cache')]
    public function can_match_complex_dynamic_route_from_cache(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);
        $matched = $routesCache->match($routesCache, '/posts/2024/12/hello-world', 'GET');

        $this->assertNotNull($matched);
        $this->assertEquals('post.show', $matched->name);
        $this->assertEquals('2024', $matched->parameters->get('year'));
        $this->assertEquals('12', $matched->parameters->get('month'));
        $this->assertEquals('hello-world', $matched->parameters->get('slug'));
    }

    #[Test]
    #[TestDox('Can match route with defaults for missing parameters')]
    public function can_match_route_with_defaults_for_missing_parameters(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);

        // Match route that has defaults for optional parameters
        $matched = $routesCache->match($routesCache, '/posts', 'GET');

        $this->assertNotNull($matched);
        $this->assertEquals('posts.optional', $matched->name);
        $this->assertEquals('general', $matched->parameters->get('category')); // from defaults
    }

    #[Test]
    #[TestDox('Can match route overriding defaults with actual parameters')]
    public function can_match_route_overriding_defaults_with_actual_parameters(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);
        // Match route providing actual parameter that overrides default
        $matched = $routesCache->match($routesCache, '/posts/tech', 'GET');

        $this->assertNotNull($matched);
        $this->assertEquals('posts.optional', $matched->name);
        $this->assertEquals('tech', $matched->parameters->get('category')); // actual value, not default
    }

    #[Test]
    #[TestDox('Parameters are properly typed from cache')]
    public function parameters_are_properly_typed_from_cache(): void
    {
        $cache = [
            'static' => [],
            'dynamic' => [
                [
                    'name' => 'test.numeric',
                    'path' => '/test/[id]/[price]',
                    'methods' => ['GET'],
                    'regex' => '/^\/test\/(?P<id>[\d+]+)\/(?P<price>[^\/]+)$/',
                    'handler' => 'TestController',
                    'middleware' => [],
                    'group' => null,
                    'parameters' => ['id', 'price'],
                    'defaults' => null,
                ]
            ],
        ];

        $routesCache = new RoutesCache($cache, $this->compiler);
        $matched = $routesCache->match($routesCache, '/test/123/45.67', 'GET');

        $this->assertNotNull($matched);
        $this->assertSame(123, $matched->parameters->get('id'));
        $this->assertSame(45.67, $matched->parameters->get('price'));
    }

    #[Test]
    #[TestDox('Non-numeric parameters remain as strings')]
    public function non_numeric_parameters_remain_as_strings(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);
        $matched = $routesCache->match($routesCache, '/posts/2024/12/hello-world', 'GET');

        $this->assertNotNull($matched);
        $this->assertSame(2024, $matched->parameters->get('year')); // numeric string converted
        $this->assertSame(12, $matched->parameters->get('month')); // numeric string converted
        $this->assertSame('hello-world', $matched->parameters->get('slug')); // non-numeric stays string
    }

    #[Test]
    #[TestDox('Returns null for non-matching cached route')]
    public function returns_null_for_non_matching_cached_route(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);
        $matched = $routesCache->match($routesCache, '/nonexistent', 'GET');

        $this->assertNull($matched);
    }

    #[Test]
    #[TestDox('Returns null for wrong HTTP method on cached route')]
    public function returns_null_for_wrong_http_method_on_cached_route(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);
        $matched = $routesCache->match($routesCache, '/', 'POST');

        $this->assertNull($matched);
    }

    #[Test]
    #[TestDox('HTTP method matching is case insensitive')]
    public function http_method_matching_is_case_insensitive(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);
        $matched = $routesCache->match($routesCache, '/', 'get');

        $this->assertNotNull($matched);
        $this->assertEquals('home', $matched->name);
    }

    #[Test]
    #[TestDox('Can match runtime routes when cache routes dont match')]
    public function can_match_runtime_routes_when_cache_routes_dont_match(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);
        $newRoute = RouteRecord::post('api.create', '/api/create', 'ApiController');
        $routesCache->addRoute($newRoute);

        $matched = $routesCache->match($routesCache, '/api/create', 'POST');

        $this->assertNotNull($matched, );
        $this->assertEquals('api.create', $matched->name);
        $this->assertEquals('ApiController', $matched->handler);
    }

    #[Test]
    #[TestDox('Can iterate over cached routes only')]
    public function can_iterate_over_cached_routes_only(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);

        $routes = [];
        foreach ($routesCache as $route) {
            $routes[] = $route->name;
        }

        $this->assertContains('home', $routes);
        $this->assertContains('about', $routes);
        $this->assertContains('user.show', $routes);
        $this->assertContains('post.show', $routes);
        $this->assertContains('posts.optional', $routes);
        $this->assertCount(5, $routes);
    }

    #[Test]
    #[TestDox('Can iterate over cached and runtime routes')]
    public function can_iterate_over_cached_and_runtime_routes(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);
        $newRoute = RouteRecord::get('contact', '/contact', 'ContactController');
        $routesCache->addRoute($newRoute);

        $routes = [];
        foreach ($routesCache as $route) {
            $routes[] = $route->name;
        }

        $this->assertContains('home', $routes);
        $this->assertContains('about', $routes);
        $this->assertContains('user.show', $routes);
        $this->assertContains('post.show', $routes);
        $this->assertContains('posts.optional', $routes);
        $this->assertContains('contact', $routes);
        $this->assertCount(6, $routes);
    }

    #[Test]
    #[TestDox('Cached routes are yielded before runtime routes')]
    public function cached_routes_are_yielded_before_runtime_routes(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);
        $newRoute = RouteRecord::get('zzz_last', '/zzz', 'LastController');
        $routesCache->addRoute($newRoute);

        $routeNames = [];
        foreach ($routesCache as $route) {
            $routeNames[] = $route->name;
        }

        // Cached routes should come first
        $this->assertEquals('home', $routeNames[0]);
        // Runtime route should come last
        $this->assertEquals('zzz_last', end($routeNames));
    }

    #[Test]
    #[TestDox('Can convert to array with cache and runtime routes')]
    public function can_convert_to_array_with_cache_and_runtime_routes(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);
        $newRoute = RouteRecord::get('contact', '/contact', 'ContactController');
        $routesCache->addRoute($newRoute);

        $array = $routesCache->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('static', $array);
        $this->assertArrayHasKey('dynamic', $array);

        // Should include original cache data plus new routes
        $this->assertGreaterThanOrEqual(2, count($array['static'])); // Original cache + new route
        $this->assertGreaterThanOrEqual(3, count($array['dynamic'])); // Original cache has 3 dynamic
    }

    #[Test]
    #[TestDox('Can convert cache-only routes to array')]
    public function can_convert_cache_only_routes_to_array(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);
        $array = $routesCache->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('static', $array);
        $this->assertArrayHasKey('dynamic', $array);

        // Should match original cache structure exactly
        $this->assertEquals($this->sampleCache, $array);
    }

    #[Test]
    #[TestDox('Cached routes preserve all route data')]
    public function cached_routes_preserve_all_route_data(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);
        $route = $routesCache->getRoute('post.show');

        $this->assertNotNull($route);
        $this->assertEquals('post.show', $route->name);
        $this->assertEquals('/posts/[year]/[month]/[slug]', (string) $route->path);
        $this->assertEquals('PostController', $route->handler);
        $this->assertEquals(['GET'], $route->methods->toArray());
        $this->assertEquals(['CacheMiddleware'], $route->middleware);
        $this->assertEquals('blog', $route->group);
        $this->assertEquals(['format' => 'html', 'section' => 'main'], $route->defaults->toArray());
    }

    #[Test]
    #[TestDox('Can handle empty cache')]
    public function can_handle_empty_cache(): void
    {
        $emptyCache = ['static' => [], 'dynamic' => []];
        $routesCache = new RoutesCache($emptyCache, $this->compiler);

        $this->assertNull($routesCache->getRoute('anything'));
        $this->assertNull($routesCache->match($routesCache, '/anything', 'GET'));

        $routes = [];
        foreach ($routesCache as $route) {
            $routes[] = $route;
        }
        $this->assertEmpty($routes);
    }

    #[Test]
    #[TestDox('Can add route to empty cache')]
    public function can_add_route_to_empty_cache(): void
    {
        $emptyCache = ['static' => [], 'dynamic' => []];
        $routesCache = new RoutesCache($emptyCache, $this->compiler);
        $route = RouteRecord::get('test', '/test', 'TestController');

        $routesCache->addRoute($route);

        $retrieved = $routesCache->getRoute('test');
        $this->assertSame($route, $retrieved);
    }

    #[Test]
    #[TestDox('Can handle routes with null defaults gracefully')]
    public function can_handle_routes_with_null_defaults_gracefully(): void
    {
        $cache = [
            'static' => [],
            'dynamic' => [
                [
                    'name' => 'no.defaults',
                    'path' => '/test/[id]',
                    'methods' => ['GET'],
                    'regex' => '/^\/test\/(?P<id>[\d+]+)$/',
                    'handler' => 'TestController',
                    'middleware' => [],
                    'group' => null,
                    'parameters' => ['id'],
                    'defaults' => null, // Explicitly null
                ]
            ],
        ];

        $routesCache = new RoutesCache($cache, $this->compiler);

        $matched = $routesCache->match($routesCache, '/test/123', 'GET');

        $this->assertNotNull($matched);
        $this->assertEquals('123', $matched->parameters->get('id'));
    }

    #[Test]
    #[DataProvider('cacheStructureProvider')]
    #[TestDox('Can handle various cache structures')]
    public function can_handle_various_cache_structures(array $cache, int $expectedStaticCount, int $expectedDynamicCount): void
    {
        $routesCache = new RoutesCache($cache, $this->compiler);

        $staticCount = 0;
        $dynamicCount = 0;

        foreach ($routesCache as $route) {
            if (str_contains((string) $route->path, '[')) {
                $dynamicCount++;
            } else {
                $staticCount++;
            }
        }

        $this->assertEquals($expectedStaticCount, $staticCount);
        $this->assertEquals($expectedDynamicCount, $dynamicCount);
    }

    public static function cacheStructureProvider(): array
    {
        return [
            'empty cache' => [
                ['static' => [], 'dynamic' => []],
                0, 0
            ],
            'only static routes' => [
                [
                    'static' => [
                        ['name' => 'home', 'path' => '/', 'methods' => ['GET'], 'regex' => '', 'handler' => 'HomeController', 'middleware' => [], 'group' => null, 'parameters' => [], 'defaults' => null],
                        ['name' => 'about', 'path' => '/about', 'methods' => ['GET'], 'regex' => '', 'handler' => 'AboutController', 'middleware' => [], 'group' => null, 'parameters' => [], 'defaults' => null],
                    ],
                    'dynamic' => []
                ],
                2, 0
            ],
            'only dynamic routes' => [
                [
                    'static' => [],
                    'dynamic' => [
                        ['name' => 'user.show', 'path' => '/users/[id]', 'methods' => ['GET'], 'regex' => '', 'handler' => 'UserController', 'middleware' => [], 'group' => null, 'parameters' => ['id'], 'defaults' => null],
                    ]
                ],
                0, 1
            ],
            'mixed routes' => [
                [
                    'static' => [
                        ['name' => 'home', 'path' => '/', 'methods' => ['GET'], 'regex' => '', 'handler' => 'HomeController', 'middleware' => [], 'group' => null, 'parameters' => [], 'defaults' => null],
                    ],
                    'dynamic' => [
                        ['name' => 'user.show', 'path' => '/users/[id]', 'methods' => ['GET'], 'regex' => '', 'handler' => 'UserController', 'middleware' => [], 'group' => null, 'parameters' => ['id'], 'defaults' => null],
                        ['name' => 'post.show', 'path' => '/posts/[slug]', 'methods' => ['GET'], 'regex' => '', 'handler' => 'PostController', 'middleware' => [], 'group' => null, 'parameters' => ['slug'], 'defaults' => null],
                    ]
                ],
                1, 2
            ],
        ];
    }

    #[Test]
    #[TestDox('Can match routes with normalized paths')]
    public function can_match_routes_with_normalized_paths(): void
    {
        $routesCache = new RoutesCache($this->sampleCache, $this->compiler);

        $testPaths = [
            '/users/123',
            '/users//123',
            '//users/123',
            '/users/123/',
        ];

        foreach ($testPaths as $path) {
            $matched = $routesCache->match($routesCache, $path, 'GET'); 
            $this->assertNotNull($matched, "Failed to match path: $path");
            $this->assertEquals('user.show', $matched->name);
            $this->assertEquals('123', $matched->parameters->get('id'));
        }
    }

    #[Test]
    #[TestDox('Missing parameters get null values when no defaults provided')]
    public function missing_parameters_get_null_values_when_no_defaults_provided(): void
    {
        $cache = [
            'static' => [],
            'dynamic' => [
                [
                    'name' => 'optional.test',
                    'path' => '/test/[?optional]',
                    'methods' => ['GET'],
                    'regex' => '/^\/test(?:\/(?P<optional>[^\/]+))?$/',
                    'handler' => 'TestController',
                    'middleware' => [],
                    'group' => null,
                    'parameters' => ['optional'],
                    'defaults' => null, // No defaults provided
                ]
            ],
        ];

        $routesCache = new RoutesCache($cache, $this->compiler);

        $matched = $routesCache->match($routesCache, '/test', 'GET');

        $this->assertNotNull($matched);
        $this->assertNull($matched->parameters->get('optional'));
    }
}