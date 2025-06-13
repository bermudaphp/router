<?php

declare(strict_types=1);

namespace Bermuda\Router\Tests;

use Bermuda\Router\RouteGroup;
use Bermuda\Router\RouteRecord;
use Bermuda\Router\Routes;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\Group;

#[Group('route-group')]
#[TestDox('RouteGroup tests')]
final class RouteGroupTest extends TestCase
{
    private \Closure $updateCallback;
    private array $callbackCalls;

    protected function setUp(): void
    {
        $this->callbackCalls = [];
        $this->updateCallback = function(string $operation, string $groupName, array $data) {
            $this->callbackCalls[] = [
                'operation' => $operation,
                'groupName' => $groupName,
                'data' => $data
            ];
        };
    }

    #[Test]
    #[TestDox('Can construct RouteGroup with basic parameters')]
    public function can_construct_route_group_with_basic_parameters(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);

        $this->assertEquals('api', $group->name);
        $this->assertEquals('/api/v1', $group->pathPrefix);
    }

    #[Test]
    #[TestDox('Can construct RouteGroup with normalized path prefix')]
    public function can_construct_route_group_with_normalized_path_prefix(): void
    {
        $group = new RouteGroup('api', '//api/v1///', $this->updateCallback);

        $this->assertEquals('/api/v1', $group->pathPrefix);
    }

    #[Test]
    #[TestDox('New RouteGroup is empty by default')]
    public function new_route_group_is_empty_by_default(): void
    {
        $group = new RouteGroup('api', '/api', $this->updateCallback);

        $this->assertTrue($group->isEmpty());
        $this->assertEquals(0, $group->count());
    }

    #[Test]
    #[TestDox('Can add route to group')]
    public function can_add_route_to_group(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);
        $route = RouteRecord::get('users', '/users', 'UserController');

        $result = $group->addRoute($route);

        $this->assertSame($group, $result);
        $this->assertFalse($group->isEmpty());
        $this->assertEquals(1, $group->count());
    }

    #[Test]
    #[TestDox('Route added to group gets prefixed name')]
    public function route_added_to_group_gets_prefixed_name(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);
        $route = RouteRecord::get('users', '/users', 'UserController');

        $group->addRoute($route);

        $this->assertTrue($group->hasRoute('api.users'));
        $this->assertNotNull($group->getRoute('api.users'));
    }

    #[Test]
    #[TestDox('Route added to group gets prefixed path')]
    public function route_added_to_group_gets_prefixed_path(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);
        $route = RouteRecord::get('users', '/users', 'UserController');

        $group->addRoute($route);

        $processedRoute = $group->getRoute('api.users');
        $this->assertEquals('/api/v1/users', (string) $processedRoute->path);
    }

    #[Test]
    #[TestDox('Route added to group calls update callback')]
    public function route_added_to_group_calls_update_callback(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);
        $route = RouteRecord::get('users', '/users', 'UserController');

        $group->addRoute($route);

        $this->assertCount(1, $this->callbackCalls);
        $this->assertEquals('route_add', $this->callbackCalls[0]['operation']);
        $this->assertEquals('api', $this->callbackCalls[0]['groupName']);
        $this->assertArrayHasKey('route', $this->callbackCalls[0]['data']);
        $this->assertArrayHasKey('originalRoute', $this->callbackCalls[0]['data']);
    }

    #[Test]
    #[TestDox('Can add route with convenience GET method')]
    public function can_add_route_with_convenience_get_method(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);

        $result = $group->get('users', '/users', 'UserController');

        $this->assertSame($group, $result);
        $this->assertTrue($group->hasRoute('api.users'));

        $route = $group->getRoute('api.users');
        $this->assertEquals(['GET'], $route->methods->toArray());
    }

    #[Test]
    #[TestDox('Can add route with convenience POST method')]
    public function can_add_route_with_convenience_post_method(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);

        $group->post('users', '/users', 'UserController');

        $route = $group->getRoute('api.users');
        $this->assertEquals(['POST'], $route->methods->toArray());
    }

    #[Test]
    #[TestDox('Can add route with convenience PUT method')]
    public function can_add_route_with_convenience_put_method(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);

        $group->put('users', '/users/[id]', 'UserController');

        $route = $group->getRoute('api.users');
        $this->assertEquals(['PUT'], $route->methods->toArray());
    }

    #[Test]
    #[TestDox('Can add route with convenience PATCH method')]
    public function can_add_route_with_convenience_patch_method(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);

        $group->patch('users', '/users/[id]', 'UserController');

        $route = $group->getRoute('api.users');
        $this->assertEquals(['PATCH'], $route->methods->toArray());
    }

    #[Test]
    #[TestDox('Can add route with convenience DELETE method')]
    public function can_add_route_with_convenience_delete_method(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);

        $group->delete('users', '/users/[id]', 'UserController');

        $route = $group->getRoute('api.users');
        $this->assertEquals(['DELETE'], $route->methods->toArray());
    }

    #[Test]
    #[TestDox('Can add route with convenience HEAD method')]
    public function can_add_route_with_convenience_head_method(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);

        $group->head('users', '/users', 'UserController');

        $route = $group->getRoute('api.users');
        $this->assertEquals(['HEAD'], $route->methods->toArray());
    }

    #[Test]
    #[TestDox('Can add route with convenience OPTIONS method')]
    public function can_add_route_with_convenience_options_method(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);

        $group->options('users', '/users', 'UserController');

        $route = $group->getRoute('api.users');
        $this->assertEquals(['OPTIONS'], $route->methods->toArray());
    }

    #[Test]
    #[TestDox('Can add route with convenience ANY method')]
    public function can_add_route_with_convenience_any_method(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);

        $group->any('users', '/users', 'UserController', ['GET', 'POST']);

        $route = $group->getRoute('api.users');
        $this->assertEquals(['GET', 'POST'], $route->methods->toArray());
    }

    #[Test]
    #[TestDox('Can add middleware to group')]
    public function can_add_middleware_to_group(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);

        $result = $group->addMiddleware('AuthMiddleware');

        $this->assertSame($group, $result);
        $this->assertEquals(['AuthMiddleware'], $group->middleware);
    }

    #[Test]
    #[TestDox('Can add multiple middleware to group')]
    public function can_add_multiple_middleware_to_group(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);

        $group->addMiddleware(['AuthMiddleware', 'CorsMiddleware']);

        $this->assertEquals(['AuthMiddleware', 'CorsMiddleware'], $group->middleware);
    }

    #[Test]
    #[TestDox('Can add middleware individually')]
    public function can_add_middleware_individually(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);

        $group->addMiddleware('AuthMiddleware')
            ->addMiddleware('CorsMiddleware');

        $this->assertEquals(['AuthMiddleware', 'CorsMiddleware'], $group->middleware);
    }

    #[Test]
    #[TestDox('Can set middleware array')]
    public function can_set_middleware_array(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);
        $group->addMiddleware('OldMiddleware');

        $result = $group->setMiddleware(['NewMiddleware1', 'NewMiddleware2']);

        $this->assertSame($group, $result);
        $this->assertEquals(['NewMiddleware1', 'NewMiddleware2'], $group->middleware);
    }

    #[Test]
    #[TestDox('Can clear middleware')]
    public function can_clear_middleware(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);
        $group->addMiddleware(['AuthMiddleware', 'CorsMiddleware']);

        $result = $group->clearMiddleware();

        $this->assertSame($group, $result);
        $this->assertEquals([], $group->middleware);
    }

    #[Test]
    #[TestDox('Routes inherit group middleware')]
    public function routes_inherit_group_middleware(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);
        $group->addMiddleware('AuthMiddleware');

        $route = RouteRecord::get('users', '/users', 'UserController')
            ->withMiddlewares(['ValidationMiddleware']);
        $group->addRoute($route);

        $processedRoute = $group->getRoute('api.users');
        $this->assertEquals(['AuthMiddleware', 'ValidationMiddleware'], $processedRoute->middleware);
    }

    #[Test]
    #[TestDox('Can set tokens for group')]
    public function can_set_tokens_for_group(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);
        $tokens = ['id' => '\d+', 'slug' => '[a-z0-9-]+'];

        $result = $group->setTokens($tokens);

        $this->assertSame($group, $result);
        $this->assertEquals($tokens, $group->tokens);
    }

    #[Test]
    #[TestDox('Routes inherit group tokens')]
    public function routes_inherit_group_tokens(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);
        $group->setTokens(['id' => '\d+', 'default' => '[a-z]+']);

        $route = RouteRecord::get('users', '/users/[id]', 'UserController')
            ->withToken('id', '\w+'); // Should override group token
        $group->addRoute($route);

        $processedRoute = $group->getRoute('api.users');
        $tokens = $processedRoute->tokens->toArray();

        $this->assertEquals('\w+', $tokens['id']); // Route-specific token takes precedence
        $this->assertEquals('[a-z]+', $tokens['default']); // Group token is inherited
    }

    #[Test]
    #[TestDox('Can iterate over group routes')]
    public function can_iterate_over_group_routes(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);
        $group->get('users', '/users', 'UserController');
        $group->post('posts', '/posts', 'PostController');

        $routeNames = [];
        foreach ($group as $route) {
            $routeNames[] = $route->name;
        }

        $this->assertEquals(['api.users', 'api.posts'], $routeNames);
    }

    #[Test]
    #[TestDox('Can count group routes')]
    public function can_count_group_routes(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);

        $this->assertEquals(0, $group->count());

        $group->get('users', '/users', 'UserController');
        $this->assertEquals(1, $group->count());

        $group->post('posts', '/posts', 'PostController');
        $this->assertEquals(2, $group->count());
    }

    #[Test]
    #[TestDox('Can check if group is empty')]
    public function can_check_if_group_is_empty(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);

        $this->assertTrue($group->isEmpty());

        $group->get('users', '/users', 'UserController');

        $this->assertFalse($group->isEmpty());
    }

    #[Test]
    #[TestDox('Can check if group has specific route')]
    public function can_check_if_group_has_specific_route(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);

        $this->assertFalse($group->hasRoute('api.users'));

        $group->get('users', '/users', 'UserController');

        $this->assertTrue($group->hasRoute('api.users'));
        $this->assertFalse($group->hasRoute('api.posts'));
    }

    #[Test]
    #[TestDox('Can get specific route from group')]
    public function can_get_specific_route_from_group(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);
        $group->get('users', '/users', 'UserController');

        $route = $group->getRoute('api.users');

        $this->assertNotNull($route);
        $this->assertEquals('api.users', $route->name);
        $this->assertEquals('UserController', $route->handler);
    }

    #[Test]
    #[TestDox('Returns null for non-existent route in group')]
    public function returns_null_for_non_existent_route_in_group(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);

        $route = $group->getRoute('api.nonexistent');

        $this->assertNull($route);
    }

    #[Test]
    #[TestDox('Can convert group to array')]
    public function can_convert_group_to_array(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);
        $group->addMiddleware('AuthMiddleware');
        $group->setTokens(['id' => '\d+']);
        $group->get('users', '/users', 'UserController');

        $array = $group->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('api', $array['name']);
        $this->assertEquals('/api/v1', $array['pathPrefix']);
        $this->assertEquals(['AuthMiddleware'], $array['middleware']);
        $this->assertEquals(['id' => '\d+'], $array['tokens']);
        $this->assertEquals(1, $array['routeCount']);
    }

    #[Test]
    #[TestDox('Can copy group with new callback')]
    public function can_copy_group_with_new_callback(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);
        $group->addMiddleware('AuthMiddleware');
        $group->get('users', '/users', 'UserController');

        $newCallbackCalls = [];
        $newCallback = function(string $operation, string $groupName, array $data) use (&$newCallbackCalls) {
            $newCallbackCalls[] = ['operation' => $operation, 'groupName' => $groupName];
        };

        $copy = RouteGroup::copy($group, $newCallback);

        $this->assertNotSame($group, $copy);
        $this->assertEquals($group->name, $copy->name);
        $this->assertEquals($group->pathPrefix, $copy->pathPrefix);
        $this->assertEquals($group->middleware, $copy->middleware);
        $this->assertEquals($group->count(), $copy->count());
    }

    #[Test]
    #[TestDox('Group handles root path combinations correctly')]
    public function group_handles_root_path_combinations_correctly(): void
    {
        $group = new RouteGroup('root', '/', $this->updateCallback);

        $group->get('home', '/', 'HomeController');
        $group->get('about', '/about', 'AboutController');

        $homeRoute = $group->getRoute('root.home');
        $aboutRoute = $group->getRoute('root.about');

        $this->assertEquals('/', (string) $homeRoute->path);
        $this->assertEquals('/about', (string) $aboutRoute->path);
    }

    #[Test]
    #[TestDox('Group handles empty route path correctly')]
    public function group_handles_empty_route_path_correctly(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);

        $group->get('root', '', 'ApiController');

        $route = $group->getRoute('api.root');
        $this->assertEquals('/api/v1', (string) $route->path);
    }

    #[Test]
    #[DataProvider('pathCombinationProvider')]
    #[TestDox('Group combines paths correctly')]
    public function group_combines_paths_correctly(string $groupPrefix, string $routePath, string $expected): void
    {
        $group = new RouteGroup('test', $groupPrefix, $this->updateCallback);

        $group->get('route', $routePath, 'TestController');

        $route = $group->getRoute('test.route');
        $this->assertEquals($expected, (string) $route->path);
    }

    public static function pathCombinationProvider(): array
    {
        return [
            'normal paths' => ['/api/v1', '/users', '/api/v1/users'],
            'root group prefix' => ['/', '/users', '/users'],
            'empty route path' => ['/api/v1', '', '/api/v1'],
            'trailing slash in prefix' => ['/api/v1/', '/users', '/api/v1/users'],
            'leading slash in route' => ['/api/v1', '/users', '/api/v1/users'],
            'both with slashes' => ['/api/v1/', '/users', '/api/v1/users'],
            'root route in group' => ['/api/v1', '/', '/api/v1'],
            'complex path' => ['/api/v1', '/users/[id]/posts', '/api/v1/users/[id]/posts'],
        ];
    }

    #[Test]
    #[TestDox('Adding middleware updates existing routes')]
    public function adding_middleware_updates_existing_routes(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);
        $group->get('users', '/users', 'UserController');

        // Clear previous callback calls
        $this->callbackCalls = [];

        $group->addMiddleware('AuthMiddleware');

        // Should trigger update for existing routes
        $this->assertGreaterThan(0, count($this->callbackCalls));
    }

    #[Test]
    #[TestDox('Setting tokens updates existing routes')]
    public function setting_tokens_updates_existing_routes(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);
        $group->get('users', '/users/[id]', 'UserController');

        // Clear previous callback calls
        $this->callbackCalls = [];

        $group->setTokens(['id' => '\d+']);

        // Should trigger update for existing routes
        $this->assertGreaterThan(0, count($this->callbackCalls));
    }

    #[Test]
    #[TestDox('Can get processed routes for updates')]
    public function can_get_processed_routes_for_updates(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);
        $group->addMiddleware('AuthMiddleware');
        $group->get('users', '/users', 'UserController');

        $processedRoutes = $group->getProcessedRoutes();

        $this->assertIsArray($processedRoutes);
        $this->assertArrayHasKey('api.users', $processedRoutes);

        $route = $processedRoutes['api.users'];
        $this->assertEquals('api.users', $route->name);
        $this->assertEquals('/api/v1/users', (string) $route->path);
        $this->assertEquals(['AuthMiddleware'], $route->middleware);
    }

    #[Test]
    #[TestDox('Can get original routes before processing')]
    public function can_get_original_routes_before_processing(): void
    {
        $group = new RouteGroup('api', '/api/v1', $this->updateCallback);
        $group->addMiddleware('AuthMiddleware');
        $group->get('users', '/users', 'UserController');

        $originalRoutes = $group->getOriginalRoutes();

        $this->assertIsArray($originalRoutes);
        $this->assertArrayHasKey('api.users', $originalRoutes);

        $route = $originalRoutes['api.users'];
        $this->assertEquals('users', $route->name); // Original name without prefix
        $this->assertEquals('/users', (string) $route->path); // Original path without prefix
        $this->assertEquals([], $route->middleware); // Original middleware without group middleware
    }

    // =================== INTEGRATION TESTS WITH ROUTES COLLECTION ===================

    #[Test]
    #[TestDox('Group middleware updates propagate to Routes collection')]
    public function group_middleware_updates_propagate_to_routes_collection(): void
    {
        $routes = new Routes();
        $group = $routes->group('api', '/api/v1');

        // Add route to group
        $group->get('users', '/users', 'UserController');

        // Verify initial state in Routes collection
        $routeInCollection = $routes->getRoute('api.users');
        $this->assertNotNull($routeInCollection);
        $this->assertEquals([], $routeInCollection->middleware);

        // Add middleware to group
        $group->addMiddleware('AuthMiddleware');

        // Verify middleware propagated to Routes collection
        $updatedRouteInCollection = $routes->getRoute('api.users');
        $this->assertEquals(['AuthMiddleware'], $updatedRouteInCollection->middleware);

        // Add more middleware
        $group->addMiddleware('CorsMiddleware');

        // Verify both middleware are present
        $finalRouteInCollection = $routes->getRoute('api.users');
        $this->assertEquals(['AuthMiddleware', 'CorsMiddleware'], $finalRouteInCollection->middleware);
    }

    #[Test]
    #[TestDox('Group tokens updates propagate to Routes collection')]
    public function group_tokens_updates_propagate_to_routes_collection(): void
    {
        $routes = new Routes();
        $group = $routes->group('api', '/api/v1');

        // Add route with parameter to group
        $group->get('users', '/users/[id]', 'UserController');

        // Verify initial state in Routes collection
        $routeInCollection = $routes->getRoute('api.users');
        $this->assertNotNull($routeInCollection);
        $this->assertEquals([], $routeInCollection->tokens->toArray());

        // Set tokens on group
        $group->setTokens(['id' => '\d+', 'slug' => '[a-z0-9-]+']);

        // Verify tokens propagated to Routes collection
        $updatedRouteInCollection = $routes->getRoute('api.users');
        $expectedTokens = ['id' => '\d+', 'slug' => '[a-z0-9-]+'];
        $this->assertEquals($expectedTokens, $updatedRouteInCollection->tokens->toArray());
    }

    #[Test]
    #[TestDox('Multiple group routes update together when group changes')]
    public function multiple_group_routes_update_together_when_group_changes(): void
    {
        $routes = new Routes();
        $group = $routes->group('api', '/api/v1');

        // Add multiple routes to group
        $group->get('users', '/users', 'UserController');
        $group->post('posts', '/posts', 'PostController');
        $group->put('categories', '/categories/[id]', 'CategoryController');

        // Verify initial state - no middleware
        $this->assertEquals([], $routes->getRoute('api.users')->middleware);
        $this->assertEquals([], $routes->getRoute('api.posts')->middleware);
        $this->assertEquals([], $routes->getRoute('api.categories')->middleware);

        // Add middleware to group
        $group->addMiddleware('AuthMiddleware');

        // Verify all routes got the middleware
        $this->assertEquals(['AuthMiddleware'], $routes->getRoute('api.users')->middleware);
        $this->assertEquals(['AuthMiddleware'], $routes->getRoute('api.posts')->middleware);
        $this->assertEquals(['AuthMiddleware'], $routes->getRoute('api.categories')->middleware);

        // Set tokens on group
        $group->setTokens(['id' => '\d+']);

        // Verify all routes got the tokens
        $this->assertEquals(['id' => '\d+'], $routes->getRoute('api.users')->tokens->toArray());
        $this->assertEquals(['id' => '\d+'], $routes->getRoute('api.posts')->tokens->toArray());
        $this->assertEquals(['id' => '\d+'], $routes->getRoute('api.categories')->tokens->toArray());
    }

    #[Test]
    #[TestDox('Route specific middleware preserved during group updates')]
    public function route_specific_middleware_preserved_during_group_updates(): void
    {
        $routes = new Routes();
        $group = $routes->group('api', '/api/v1');

        // Add route with its own middleware
        $routeWithMiddleware = RouteRecord::get('users', '/users', 'UserController')
            ->withMiddlewares(['ValidationMiddleware', 'CacheMiddleware']);
        $group->addRoute($routeWithMiddleware);

        // Verify initial middleware state
        $routeInCollection = $routes->getRoute('api.users');
        $this->assertEquals(['ValidationMiddleware', 'CacheMiddleware'], $routeInCollection->middleware);

        // Add group middleware
        $group->addMiddleware('AuthMiddleware');

        // Verify group middleware is prepended, route middleware preserved
        $updatedRoute = $routes->getRoute('api.users');
        $this->assertEquals(['AuthMiddleware', 'ValidationMiddleware', 'CacheMiddleware'], $updatedRoute->middleware);

        // Add more group middleware
        $group->addMiddleware('CorsMiddleware');

        // Verify correct order: group middleware first, then route middleware
        $finalRoute = $routes->getRoute('api.users');
        $this->assertEquals([
            'AuthMiddleware',
            'CorsMiddleware',
            'ValidationMiddleware',
            'CacheMiddleware'
        ], $finalRoute->middleware);
    }

    #[Test]
    #[TestDox('Route specific tokens override group tokens after updates')]
    public function route_specific_tokens_override_group_tokens_after_updates(): void
    {
        $routes = new Routes();
        $group = $routes->group('api', '/api/v1');

        // Add route with specific token
        $routeWithTokens = RouteRecord::get('users', '/users/[id]', 'UserController')
            ->withToken('id', '\w+'); // Route-specific token
        $group->addRoute($routeWithTokens);

        // Set group tokens (should not override route-specific tokens)
        $group->setTokens(['id' => '\d+', 'slug' => '[a-z0-9-]+']);

        // Verify route-specific token takes precedence, group token is inherited
        $routeInCollection = $routes->getRoute('api.users');
        $tokens = $routeInCollection->tokens->toArray();

        $this->assertEquals('\w+', $tokens['id']); // Route-specific token wins
        $this->assertEquals('[a-z0-9-]+', $tokens['slug']); // Group token inherited
    }

    #[Test]
    #[TestDox('Group middleware changes work correctly with setMiddleware')]
    public function group_middleware_changes_work_correctly_with_set_middleware(): void
    {
        $routes = new Routes();
        $group = $routes->group('api', '/api/v1');

        // Add routes
        $group->get('users', '/users', 'UserController');
        $group->get('posts', '/posts', 'PostController');

        // Set initial middleware
        $group->setMiddleware(['AuthMiddleware', 'CorsMiddleware']);

        // Verify middleware applied
        $this->assertEquals(['AuthMiddleware', 'CorsMiddleware'], $routes->getRoute('api.users')->middleware);
        $this->assertEquals(['AuthMiddleware', 'CorsMiddleware'], $routes->getRoute('api.posts')->middleware);

        // Replace middleware completely
        $group->setMiddleware(['NewAuthMiddleware', 'RateLimitMiddleware']);

        // Verify old middleware removed, new middleware applied
        $this->assertEquals(['NewAuthMiddleware', 'RateLimitMiddleware'], $routes->getRoute('api.users')->middleware);
        $this->assertEquals(['NewAuthMiddleware', 'RateLimitMiddleware'], $routes->getRoute('api.posts')->middleware);
    }

    #[Test]
    #[TestDox('Group middleware clearing works correctly')]
    public function group_middleware_clearing_works_correctly(): void
    {
        $routes = new Routes();
        $group = $routes->group('api', '/api/v1');

        // Add route with its own middleware
        $routeWithMiddleware = RouteRecord::get('users', '/users', 'UserController')
            ->withMiddleware('RouteMiddleware');
        $group->addRoute($routeWithMiddleware);

        // Add group middleware
        $group->addMiddleware('GroupMiddleware');

        // Verify combined middleware
        $this->assertEquals(['GroupMiddleware', 'RouteMiddleware'], $routes->getRoute('api.users')->middleware);

        // Clear group middleware
        $group->clearMiddleware();

        // Verify only route middleware remains
        $this->assertEquals(['RouteMiddleware'], $routes->getRoute('api.users')->middleware);
    }

    #[Test]
    #[TestDox('Adding routes after group configuration inherits settings')]
    public function adding_routes_after_group_configuration_inherits_settings(): void
    {
        $routes = new Routes();
        $group = $routes->group('api', '/api/v1');

        // Configure group first
        $group->addMiddleware('AuthMiddleware');
        $group->setTokens(['id' => '\d+']);

        // Add route after configuration
        $group->get('users', '/users/[id]', 'UserController');

        // Verify route inherits group configuration
        $routeInCollection = $routes->getRoute('api.users');
        $this->assertEquals(['AuthMiddleware'], $routeInCollection->middleware);
        $this->assertEquals(['id' => '\d+'], $routeInCollection->tokens->toArray());
    }

    #[Test]
    #[TestDox('Route collection preserves route order during group updates')]
    public function route_collection_preserves_route_order_during_group_updates(): void
    {
        $routes = new Routes();

        // Add some routes outside group
        $routes->addRoute(RouteRecord::get('home', '/', 'HomeController'));

        // Create group and add routes
        $group = $routes->group('api', '/api/v1');
        $group->get('users', '/users', 'UserController');
        $group->get('posts', '/posts', 'PostController');

        // Add more routes outside group
        $routes->addRoute(RouteRecord::get('about', '/about', 'AboutController'));

        // Get initial route order
        $initialRoutes = [];
        foreach ($routes as $route) {
            $initialRoutes[] = $route->name;
        }

        $this->assertEquals(['home', 'api.users', 'api.posts', 'about'], $initialRoutes);

        // Update group middleware
        $group->addMiddleware('AuthMiddleware');

        // Verify route order preserved
        $updatedRoutes = [];
        foreach ($routes as $route) {
            $updatedRoutes[] = $route->name;
        }

        $this->assertEquals(['home', 'api.users', 'api.posts', 'about'], $updatedRoutes);

        // Verify middleware only applied to group routes
        $this->assertEquals([], $routes->getRoute('home')->middleware);
        $this->assertEquals(['AuthMiddleware'], $routes->getRoute('api.users')->middleware);
        $this->assertEquals(['AuthMiddleware'], $routes->getRoute('api.posts')->middleware);
        $this->assertEquals([], $routes->getRoute('about')->middleware);
    }
}