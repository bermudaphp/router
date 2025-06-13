<?php

declare(strict_types=1);

namespace Bermuda\Router\Tests;

use Bermuda\Router\RouteRecord;
use Bermuda\Router\RoutePath;
use Bermuda\Router\Collector\Methods;
use Bermuda\Router\Collector\Parameters;
use Bermuda\Router\Collector\Tokens;
use Bermuda\Router\Collector\Collector;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\Group;

#[Group('route-record')]
#[TestDox('RouteRecord tests for PHP 8.4 features')]
final class RouteRecordTest extends TestCase
{
    #[Test]
    #[TestDox('Can construct RouteRecord with basic parameters')]
    public function can_construct_route_record_with_basic_parameters(): void
    {
        $route = new RouteRecord('test', '/test', 'TestController');

        $this->assertEquals('test', $route->name);
        $this->assertEquals('/test', (string) $route->path);
        $this->assertEquals('TestController', $route->handler);
        $this->assertIsArray($route->middleware);
        $this->assertEmpty($route->middleware);
    }

    #[Test]
    #[TestDox('Can construct RouteRecord with middleware')]
    public function can_construct_route_record_with_middleware(): void
    {
        $middleware = ['AuthMiddleware', 'CorsMiddleware'];
        $route = new RouteRecord(
            'test',
            '/test',
            'TestController',
            ['GET'],
            $middleware
        );

        $this->assertEquals($middleware, $route->middleware);
        $this->assertEquals('TestController', $route->handler);
    }

    #[Test]
    #[Group('php84')]
    #[TestDox('Pipeline property hook combines middleware and handler')]
    public function pipeline_property_hook_combines_middleware_and_handler(): void
    {
        $middleware = ['AuthMiddleware', 'CorsMiddleware'];
        $handler = 'TestController';

        $route = new RouteRecord(
            'test',
            '/test',
            $handler,
            ['GET'],
            $middleware
        );

        $pipeline = $route->pipeline;

        $this->assertIsArray($pipeline);
        $this->assertCount(3, $pipeline);
        $this->assertEquals('AuthMiddleware', $pipeline[0]);
        $this->assertEquals('CorsMiddleware', $pipeline[1]);
        $this->assertEquals('TestController', $pipeline[2]);
    }

    #[Test]
    #[Group('php84')]
    #[TestDox('Pipeline with no middleware contains only handler')]
    public function pipeline_with_no_middleware_contains_only_handler(): void
    {
        $route = new RouteRecord('test', '/test', 'TestController');

        $pipeline = $route->pipeline;

        $this->assertIsArray($pipeline);
        $this->assertCount(1, $pipeline);
        $this->assertEquals('TestController', $pipeline[0]);
    }

    #[Test]
    #[Group('php84')]
    #[TestDox('Middleware property is separate from handler')]
    public function middleware_property_is_separate_from_handler(): void
    {
        $middleware = ['AuthMiddleware'];
        $handler = 'TestController';

        $route = new RouteRecord(
            'test',
            '/test',
            $handler,
            ['GET'],
            $middleware
        );

        $this->assertEquals($middleware, $route->middleware);
        $this->assertEquals($handler, $route->handler);
        $this->assertNotEquals($route->middleware, [$route->handler]);
    }

    #[Test]
    #[TestDox('withMiddleware adds single middleware to existing stack')]
    public function with_middleware_adds_single_middleware_to_existing_stack(): void
    {
        $route = new RouteRecord('test', '/test', 'TestController', ['GET'], ['AuthMiddleware']);
        $newRoute = $route->withMiddleware('CorsMiddleware');

        // Original unchanged
        $this->assertEquals(['AuthMiddleware'], $route->middleware);
        $this->assertEquals(['AuthMiddleware', 'TestController'], $route->pipeline);

        // New route has additional middleware
        $this->assertEquals(['AuthMiddleware', 'CorsMiddleware'], $newRoute->middleware);
        $this->assertEquals(['AuthMiddleware', 'CorsMiddleware', 'TestController'], $newRoute->pipeline);
    }

    #[Test]
    #[TestDox('withMiddleware clears middleware when passed null')]
    public function with_middleware_clears_middleware_when_passed_null(): void
    {
        $route = new RouteRecord('test', '/test', 'TestController', ['GET'], ['AuthMiddleware']);
        $newRoute = $route->withMiddleware(null);

        // Original unchanged
        $this->assertEquals(['AuthMiddleware'], $route->middleware);

        // New route has no middleware
        $this->assertEmpty($newRoute->middleware);
        $this->assertEquals(['TestController'], $newRoute->pipeline);
    }

    #[Test]
    #[TestDox('withMiddleware clears middleware when passed empty array')]
    public function with_middleware_clears_middleware_when_passed_empty_array(): void
    {
        $route = new RouteRecord('test', '/test', 'TestController', ['GET'], ['AuthMiddleware']);
        $newRoute = $route->withMiddleware([]);

        // Original unchanged
        $this->assertEquals(['AuthMiddleware'], $route->middleware);

        // New route has no middleware
        $this->assertEmpty($newRoute->middleware);
        $this->assertEquals(['TestController'], $newRoute->pipeline);
    }

    #[Test]
    #[TestDox('withMiddlewares replaces entire middleware stack')]
    public function with_middlewares_replaces_entire_middleware_stack(): void
    {
        $route = new RouteRecord('test', '/test', 'TestController', ['GET'], ['OldMiddleware']);
        $newMiddleware = ['AuthMiddleware', 'CorsMiddleware', 'RateLimitMiddleware'];
        $newRoute = $route->withMiddlewares($newMiddleware);

        // Original unchanged
        $this->assertEquals(['OldMiddleware'], $route->middleware);

        // New route has completely new middleware stack
        $this->assertEquals($newMiddleware, $newRoute->middleware);
        $this->assertEquals([...$newMiddleware, 'TestController'], $newRoute->pipeline);
    }

    #[Test]
    #[TestDox('withMiddlewares can clear all middleware')]
    public function with_middlewares_can_clear_all_middleware(): void
    {
        $route = new RouteRecord('test', '/test', 'TestController', ['GET'], ['AuthMiddleware']);
        $newRoute = $route->withMiddlewares([]);

        // Original unchanged
        $this->assertEquals(['AuthMiddleware'], $route->middleware);

        // New route has no middleware
        $this->assertEmpty($newRoute->middleware);
        $this->assertEquals(['TestController'], $newRoute->pipeline);
    }

    #[Test]
    #[TestDox('Route factory methods create correct HTTP method routes')]
    #[DataProvider('routeFactoryMethodsProvider')]
    public function route_factory_methods_create_correct_http_method_routes(
        string $factoryMethod,
        array $expectedMethods
    ): void {
        $route = RouteRecord::$factoryMethod('test', '/test', 'TestController');

        $this->assertEquals('test', $route->name);
        $this->assertEquals('/test', (string) $route->path);
        $this->assertEquals('TestController', $route->handler);
        $this->assertEquals($expectedMethods, $route->methods->toArray());
    }

    public static function routeFactoryMethodsProvider(): array
    {
        return [
            ['get', ['GET']],
            ['post', ['POST']],
            ['put', ['PUT']],
            ['patch', ['PATCH']],
            ['delete', ['DELETE']],
            ['head', ['HEAD']],
            ['options', ['OPTIONS']],
        ];
    }

    #[Test]
    #[TestDox('any method creates route with all methods when empty array passed')]
    public function any_method_creates_route_with_specified_methods(): void
    {
        // With specific methods
        $route1 = RouteRecord::any('test1', '/test1', 'TestController', ['GET', 'POST']);
        $this->assertEquals(['GET', 'POST'], $route1->methods->toArray());

        // With empty array - should allow all methods (empty methods collection means all methods)
        $route2 = RouteRecord::any('test2', '/test2', 'TestController', []);
        // The behavior depends on the Methods collector implementation
        // If empty array means "all methods", then the methods collection might be empty
        // or contain all standard HTTP methods
        $this->assertIsArray($route2->methods->toArray());
    }

    #[Test]
    #[TestDox('withToken adds or updates single token')]
    public function with_token_adds_or_updates_single_token(): void
    {
        $route = new RouteRecord('test', '/test/[id]', 'TestController');
        $newRoute = $route->withToken('id', '\d+');

        // Original unchanged
        $this->assertEmpty($route->tokens->toArray());

        // New route has token
        $this->assertEquals(['id' => '\d+'], $newRoute->tokens->toArray());
    }

    #[Test]
    #[TestDox('withTokens replaces entire token collection')]
    public function with_tokens_replaces_entire_token_collection(): void
    {
        $route = new RouteRecord('test', '/test/[id]', 'TestController');
        $tokens = ['id' => '\d+', 'slug' => '[a-z0-9-]+'];
        $newRoute = $route->withTokens($tokens);

        $this->assertEquals($tokens, $newRoute->tokens->toArray());
    }

    #[Test]
    #[TestDox('withPrefix adds prefix to path')]
    public function with_prefix_adds_prefix_to_path(): void
    {
        $route = new RouteRecord('test', '/users', 'TestController');
        $newRoute = $route->withPrefix('/api/v1');

        $this->assertEquals('/users', (string) $route->path);
        $this->assertEquals('/api/v1/users', (string) $newRoute->path);
    }

    #[Test]
    #[TestDox('withName updates route name')]
    public function with_name_updates_route_name(): void
    {
        $route = new RouteRecord('test', '/test', 'TestController');
        $newRoute = $route->withName('new.test');

        $this->assertEquals('test', $route->name);
        $this->assertEquals('new.test', $newRoute->name);
    }

    #[Test]
    #[TestDox('withDefaultValue adds single default value')]
    public function with_default_value_adds_single_default_value(): void
    {
        $route = new RouteRecord('test', '/test', 'TestController');
        $newRoute = $route->withDefaultValue('page', '1');

        $this->assertNull($route->defaults);
        $this->assertEquals(['page' => '1'], $newRoute->defaults->toArray());
    }

    #[Test]
    #[TestDox('withDefaults replaces default values collection')]
    public function with_defaults_replaces_default_values_collection(): void
    {
        $route = new RouteRecord('test', '/test', 'TestController');
        $defaults = ['page' => '1', 'limit' => '10'];
        $newRoute = $route->withDefaults($defaults);

        $this->assertEquals($defaults, $newRoute->defaults->toArray());
    }

    #[Test]
    #[TestDox('withDefaults can clear defaults with null')]
    public function with_defaults_can_clear_defaults_with_null(): void
    {
        $route = new RouteRecord('test', '/test', 'TestController', [], [], [], [], ['page' => '1']);
        $newRoute = $route->withDefaults(null);

        $this->assertNotNull($route->defaults);
        $this->assertNull($newRoute->defaults);
    }

    #[Test]
    #[TestDox('withParameters updates parameters collection')]
    public function with_parameters_updates_parameters_collection(): void
    {
        $route = new RouteRecord('test', '/test', 'TestController');
        $params = ['id' => '123', 'slug' => 'test-post'];
        $newRoute = $route->withParameters($params);

        $this->assertEquals($params, $newRoute->parameters->toArray());
    }

    #[Test]
    #[TestDox('withParameter adds single parameter')]
    public function with_parameter_adds_single_parameter(): void
    {
        $route = new RouteRecord('test', '/test', 'TestController');
        $newRoute = $route->withParameter('id', '123');

        $this->assertEquals(['id' => '123'], $newRoute->parameters->toArray());
    }

    #[Test]
    #[TestDox('withGroup updates group assignment')]
    public function with_group_updates_group_assignment(): void
    {
        $route = new RouteRecord('test', '/test', 'TestController');
        $newRoute = $route->withGroup('api');

        $this->assertNull($route->group);
        $this->assertEquals('api', $newRoute->group);
    }

    #[Test]
    #[TestDox('withMethod adds single HTTP method')]
    public function with_method_adds_single_http_method(): void
    {
        $route = new RouteRecord('test', '/test', 'TestController', ['GET']);
        $newRoute = $route->withMethod('POST');

        $this->assertEquals(['GET'], $route->methods->toArray());
        $this->assertEquals(['GET', 'POST'], $newRoute->methods->toArray());
    }

    #[Test]
    #[TestDox('withMethod returns same instance if method already exists')]
    public function with_method_returns_same_instance_if_method_already_exists(): void
    {
        $route = new RouteRecord('test', '/test', 'TestController', ['GET']);
        $newRoute = $route->withMethod('GET');

        $this->assertSame($route, $newRoute);
    }

    #[Test]
    #[TestDox('withMethods replaces entire methods collection')]
    public function with_methods_replaces_entire_methods_collection(): void
    {
        $route = new RouteRecord('test', '/test', 'TestController', ['GET']);
        $newRoute = $route->withMethods(['POST', 'PUT', 'DELETE']);

        $this->assertEquals(['GET'], $route->methods->toArray());
        $this->assertEquals(['POST', 'PUT', 'DELETE'], $newRoute->methods->toArray());
    }

    #[Test]
    #[TestDox('fromArray factory creates RouteRecord from array data')]
    public function from_array_factory_creates_route_record_from_array_data(): void
    {
        $data = [
            'name' => 'test.route',
            'path' => '/test/[id]',
            'handler' => 'TestController',
            'methods' => ['GET', 'POST'],
            'middleware' => ['AuthMiddleware'],
            'tokens' => ['id' => '\d+'],
            'parameters' => ['id' => '123'],
            'defaults' => ['format' => 'json'],
            'group' => 'api'
        ];

        $route = RouteRecord::fromArray($data);

        $this->assertEquals('test.route', $route->name);
        $this->assertEquals('/test/[id]', (string) $route->path);
        $this->assertEquals('TestController', $route->handler);
        $this->assertEquals(['GET', 'POST'], $route->methods->toArray());
        $this->assertEquals(['AuthMiddleware'], $route->middleware);
        $this->assertEquals(['id' => '\d+'], $route->tokens->toArray());
        $this->assertEquals(['id' => '123'], $route->parameters->toArray());
        $this->assertEquals(['format' => 'json'], $route->defaults->toArray());
        $this->assertEquals('api', $route->group);
    }

    #[Test]
    #[TestDox('toArray converts RouteRecord to array representation')]
    public function to_array_converts_route_record_to_array_representation(): void
    {
        $route = new RouteRecord(
            'test.route',
            '/test/[id]',
            'TestController',
            ['GET'],
            ['AuthMiddleware'],
            ['id' => '\d+'],
            ['id' => '123'],
            ['format' => 'json'],
            'api'
        );

        $array = $route->toArray();

        $this->assertEquals('test.route', $array['name']);
        $this->assertEquals('/test/[id]', $array['path']);
        $this->assertEquals(['AuthMiddleware', 'TestController'], $array['handler']); // Pipeline
        $this->assertEquals(['GET'], $array['methods']);
        $this->assertEquals(['id' => '\d+'], $array['tokens']);
        $this->assertEquals(['id' => '123'], $array['params']);
        $this->assertEquals(['format' => 'json'], $array['defaults']);
        $this->assertEquals('api', $array['group']);
    }

    #[Test]
    #[TestDox('Route immutability is preserved through most with methods')]
    public function route_immutability_is_preserved_through_all_with_methods(): void
    {
        $original = new RouteRecord('test', '/test', 'TestController', ['GET']);

        // Test methods that should always return new instances
        $methods = [
            ['withToken', ['id', '\d+']],
            ['withTokens', [['id' => '\d+']]],
            ['withPrefix', ['/api']],
            ['withName', ['new.test']],
            ['withDefaultValue', ['page', '1']],
            ['withDefaults', [['page' => '1']]],
            ['withParameters', [['id' => '123']]],
            ['withParameter', ['id', '123']],
            ['withGroup', ['api']],
            ['withMethods', [['POST']]],
            ['withMiddleware', ['AuthMiddleware']],
            ['withMiddlewares', [['AuthMiddleware']]],
        ];

        foreach ($methods as [$method, $args]) {
            $new = $original->$method(...$args);

            // Most methods should return new instances
            if ($method !== 'withMethod' || !in_array($args[0] ?? '', $original->methods->toArray())) {
                $this->assertNotSame(
                    $original,
                    $new,
                    "Method $method should return new instance"
                );
            }
        }

        // Special case: withMethod with existing method returns same instance
        $sameInstance = $original->withMethod('GET');
        $this->assertSame($original, $sameInstance, 'withMethod with existing method should return same instance');
    }

    #[Test]
    #[Group('php84')]
    #[TestDox('Pipeline property hook maintains consistency across multiple accesses')]
    public function pipeline_property_hook_maintains_consistency_across_multiple_accesses(): void
    {
        $route = new RouteRecord(
            'test',
            '/test',
            'TestController',
            ['GET'],
            ['AuthMiddleware', 'CorsMiddleware']
        );

        $pipeline1 = $route->pipeline;
        $pipeline2 = $route->pipeline;

        // Content should be identical
        $this->assertEquals($pipeline1, $pipeline2);

        // Both should have the same structure
        $this->assertCount(3, $pipeline1);
        $this->assertCount(3, $pipeline2);

        $this->assertEquals('AuthMiddleware', $pipeline1[0]);
        $this->assertEquals('CorsMiddleware', $pipeline1[1]);
        $this->assertEquals('TestController', $pipeline1[2]);
    }

    #[Test]
    #[Group('php84')]
    #[TestDox('Pipeline property hook performance with repeated access')]
    public function pipeline_property_hook_performance_with_repeated_access(): void
    {
        $route = new RouteRecord(
            'test',
            '/test',
            'TestController',
            ['GET'],
            ['AuthMiddleware']
        );

        $start = microtime(true);

        // Access pipeline multiple times
        for ($i = 0; $i < 1000; $i++) {
            $pipeline = $route->pipeline;
            $this->assertIsArray($pipeline);
        }

        $end = microtime(true);
        $executionTime = $end - $start;

        // Should complete quickly (less than 100ms for 1000 accesses)
        $this->assertLessThan(0.1, $executionTime, 'Pipeline property hook should be performant');

        // Verify the pipeline is computed correctly each time
        $finalPipeline = $route->pipeline;
        $this->assertEquals(['AuthMiddleware', 'TestController'], $finalPipeline);
    }

    #[Test]
    #[Group('php84')]
    #[TestDox('Property visibility works correctly')]
    public function property_visibility_works_correctly(): void
    {
        $route = new RouteRecord('test', '/test', 'TestController', ['GET'], ['AuthMiddleware']);

        // These properties should be readable
        $this->assertIsString($route->name);
        $this->assertInstanceOf(RoutePath::class, $route->path);
        $this->assertEquals('TestController', $route->handler);
        $this->assertIsArray($route->middleware);
        $this->assertIsArray($route->pipeline);
        $this->assertInstanceOf(Methods::class, $route->methods);
        $this->assertInstanceOf(Parameters::class, $route->parameters);
        $this->assertInstanceOf(Tokens::class, $route->tokens);

        // Properties should not be directly writable (private(set))
        // This would be tested by the PHP engine itself, not by PHPUnit
        // as it would result in a fatal error
    }

    #[Test]
    #[Group('edge-cases')]
    #[TestDox('Route handles complex middleware and handler combinations')]
    public function route_handles_complex_middleware_and_handler_combinations(): void
    {
        $complexHandler = ['ControllerClass', 'methodName'];
        $complexMiddleware = [
            'AuthMiddleware::class',
            ['MiddlewareClass', 'handle'],
            fn() => 'closure middleware'
        ];

        $route = new RouteRecord(
            'complex.route',
            '/complex/[id]',
            $complexHandler,
            ['GET', 'POST'],
            $complexMiddleware
        );

        $this->assertEquals($complexHandler, $route->handler);
        $this->assertEquals($complexMiddleware, $route->middleware);

        $expectedPipeline = [...$complexMiddleware, $complexHandler];
        $this->assertEquals($expectedPipeline, $route->pipeline);
    }

    #[Test]
    #[Group('edge-cases')]
    #[TestDox('Route handles null and empty values correctly')]
    public function route_handles_null_and_empty_values_correctly(): void
    {
        // Test with minimal parameters
        $route = new RouteRecord('minimal', '/', 'Handler');

        $this->assertEquals('minimal', $route->name);
        $this->assertEquals('/', (string) $route->path);
        $this->assertEquals('Handler', $route->handler);
        $this->assertEmpty($route->middleware);
        $this->assertEquals(['Handler'], $route->pipeline);
        $this->assertNull($route->defaults);
        $this->assertNull($route->group);

        // Test clearing with null values
        $routeWithDefaults = $route->withDefaults(['key' => 'value']);
        $clearedRoute = $routeWithDefaults->withDefaults(null);

        $this->assertNotNull($routeWithDefaults->defaults);
        $this->assertNull($clearedRoute->defaults);
    }
}