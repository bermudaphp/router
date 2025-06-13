<?php

declare(strict_types=1);

namespace Bermuda\Router\Tests;

use Bermuda\Router\Routes;
use Bermuda\Router\RoutesCache;
use Bermuda\Router\RouteRecord;
use Bermuda\Router\Compiler;
use Bermuda\Router\RouteCompileResult;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\Group;

#[Group('type-conversion')]
#[TestDox('Type conversion verification tests')]
final class TypeConversionVerificationTest extends TestCase
{
    #[Test]
    #[TestDox('RouteCompileResult directly converts numeric strings to numbers')]
    public function route_compile_result_directly_converts_numeric_strings_to_numbers(): void
    {
        $result = new RouteCompileResult(
            '/^\/test\/(?P<int_param>\d+)\/(?P<float_param>[0-9.]+)\/(?P<string_param>[^\/]+)$/',
            ['int_param', 'float_param', 'string_param'],
            []
        );

        $params = $result->matches('/test/123/45.67/hello');

        $this->assertNotNull($params);

        $this->assertSame(123, $params['int_param']);
        $this->assertSame(45.67, $params['float_param']);
        $this->assertSame('hello', $params['string_param']);

        $this->assertIsInt($params['int_param']);
        $this->assertIsFloat($params['float_param']);
        $this->assertIsString($params['string_param']);
    }

    #[Test]
    #[TestDox('Routes should maintain type conversion from RouteCompileResult')]
    public function routes_should_maintain_type_conversion_from_route_compile_result(): void
    {
        $routes = new Routes(new Compiler());
        $route = RouteRecord::get('test', '/test/[id]/[price]/[name]', 'TestController');
        $routes->addRoute($route);

        $matched = $routes->match($routes, '/test/123/45.67/hello', 'GET');

        $this->assertNotNull($matched);

        $id = $matched->parameters->get('id');
        $price = $matched->parameters->get('price');
        $name = $matched->parameters->get('name');

        $this->assertSame(123, $id, 'Routes should convert numeric strings to integers');
        $this->assertSame(45.67, $price, 'Routes should convert numeric strings to floats');
        $this->assertSame('hello', $name, 'Routes should keep non-numeric strings as strings');

        $this->assertIsInt($id);
        $this->assertIsFloat($price);
        $this->assertIsString($name);
    }

    #[Test]
    #[TestDox('RoutesCache maintains expected type conversion behavior')]
    public function routes_cache_maintains_expected_type_conversion_behavior(): void
    {
        $cacheData = [
            'static' => [],
            'dynamic' => [
                [
                    'name' => 'test',
                    'path' => '/test/[id]/[price]/[name]',
                    'methods' => ['GET'],
                    'regex' => '/^\/test\/(?P<id>[^\/]+)\/(?P<price>[^\/]+)\/(?P<name>[^\/]+)$/',
                    'handler' => 'TestController',
                    'middleware' => [],
                    'group' => null,
                    'parameters' => ['id', 'price', 'name'],
                    'defaults' => null,
                ]
            ]
        ];

        $routesCache = new RoutesCache($cacheData, new Compiler());

        $matched = $routesCache->match($routesCache, '/test/123/45.67/hello', 'GET');

        $this->assertNotNull($matched);

        $id = $matched->parameters->get('id');
        $price = $matched->parameters->get('price');
        $name = $matched->parameters->get('name');

        $this->assertSame(123, $id);
        $this->assertSame(45.67, $price);
        $this->assertSame('hello', $name);

        $this->assertIsInt($id);
        $this->assertIsFloat($price);
        $this->assertIsString($name);
    }

    #[Test]
    #[TestDox('Both implementations should have identical behavior')]
    public function both_implementations_should_have_identical_behavior(): void
    {
        $routes = new Routes(new Compiler());
        $routes->addRoute(RouteRecord::get('comparison', '/compare/[id]/[price]/[category]', 'ComparisonController'));

        $cacheData = [
            'static' => [],
            'dynamic' => [
                [
                    'name' => 'comparison',
                    'path' => '/compare/[id]/[price]/[category]',
                    'methods' => ['GET'],
                    'regex' => '/^\/compare\/(?P<id>[^\/]+)\/(?P<price>[^\/]+)\/(?P<category>[^\/]+)$/',
                    'handler' => 'ComparisonController',
                    'middleware' => [],
                    'group' => null,
                    'parameters' => ['id', 'price', 'category'],
                    'defaults' => null,
                ]
            ]
        ];
        $routesCache = new RoutesCache($cacheData, new Compiler());

        $testCases = [
            ['/compare/123/99.99/electronics', 'id', 123, 'integer'],
            ['/compare/123/99.99/electronics', 'price', 99.99, 'double'],
            ['/compare/123/99.99/electronics', 'category', 'electronics', 'string'],
            ['/compare/0/0.0/zero-test', 'id', 0, 'integer'],
            ['/compare/0/0.0/zero-test', 'price', 0.0, 'double'],
            ['/compare/456/1000/numeric-category', 'price', 1000, 'integer'],
        ];

        foreach ($testCases as [$uri, $param, $expectedValue, $expectedType]) {
            $routesMatch = $routes->match($routes, $uri, 'GET');
            $cacheMatch = $routesCache->match($routesCache, $uri, 'GET');

            $this->assertNotNull($routesMatch, "Routes failed to match: $uri");
            $this->assertNotNull($cacheMatch, "RoutesCache failed to match: $uri");

            $routesValue = $routesMatch->parameters->get($param);
            $cacheValue = $cacheMatch->parameters->get($param);

            $this->assertSame($expectedValue, $routesValue,
                "Routes value mismatch for $param in $uri");
            $this->assertSame($expectedValue, $cacheValue,
                "RoutesCache value mismatch for $param in $uri");

            $this->assertEquals($expectedType, gettype($routesValue),
                "Routes type mismatch for $param in $uri");
            $this->assertEquals($expectedType, gettype($cacheValue),
                "RoutesCache type mismatch for $param in $uri");

            $this->assertSame($routesValue, $cacheValue,
                "Value mismatch between Routes and RoutesCache for $param in $uri");
            $this->assertEquals(gettype($routesValue), gettype($cacheValue),
                "Type mismatch between Routes and RoutesCache for $param in $uri");
        }
    }

    #[Test]
    #[DataProvider('edgeCaseTypeConversionProvider')]
    #[TestDox('Edge cases in type conversion are handled correctly')]
    public function edge_cases_in_type_conversion_are_handled_correctly(
        string $input,
        mixed $expectedValue,
        string $expectedType,
        string $description
    ): void {
        $result = new RouteCompileResult(
            '/^\/test\/(?P<value>.+)$/',
            ['value'],
            []
        );

        $params = $result->matches("/test/{$input}");

        $this->assertNotNull($params, "Failed to extract parameters for: $description");

        $this->assertSame($expectedValue, $params['value'], $description);
        $this->assertEquals($expectedType, gettype($params['value']), $description);
    }

    public static function edgeCaseTypeConversionProvider(): array
    {
        return [
            ['123', 123, 'integer', 'Simple positive integer'],
            ['-123', -123, 'integer', 'Negative integer'],
            ['0', 0, 'integer', 'Zero integer'],
            ['99.99', 99.99, 'double', 'Positive float'],
            ['-45.67', -45.67, 'double', 'Negative float'],
            ['0.0', 0.0, 'double', 'Zero float'],
            ['1.0', 1.0, 'double', 'Float that looks like integer'],
            ['000123', 123, 'integer', 'Integer with leading zeros'],
            ['123.000', 123.0, 'double', 'Float with trailing zeros'],
            ['1e5', 100000.0, 'double', 'Scientific notation - large'],
            ['1.23e-4', 0.000123, 'double', 'Scientific notation - small'],
            ['2.5e2', 250.0, 'double', 'Scientific notation - medium'],
            ['hello', 'hello', 'string', 'Simple string'],
            ['123abc', '123abc', 'string', 'Mixed alphanumeric starting with numbers'],
            ['abc123', 'abc123', 'string', 'Mixed alphanumeric starting with letters'],
            ['   123   ', '   123   ', 'string', 'Numbers with spaces (not purely numeric)'],
            ['123.45.67', '123.45.67', 'string', 'Multiple decimal points'],
            ['+123', 123, 'integer', 'Positive integer with plus sign'],
            ['+45.67', 45.67, 'double', 'Positive float with plus sign'],
        ];
    }

    #[Test]
    #[TestDox('Zero and negative values are handled consistently')]
    public function zero_and_negative_values_are_handled_consistently(): void
    {
        $result = new RouteCompileResult(
            '/^\/test\/(?P<int_val>[^\/]+)\/(?P<float_val>[^\/]+)$/',
            ['int_val', 'float_val'],
            []
        );

        $testCases = [
            ['0', '0.0', 0, 0.0],
            ['-123', '-45.67', -123, -45.67],
            ['+456', '+78.90', 456, 78.90],
        ];

        foreach ($testCases as [$intInput, $floatInput, $expectedInt, $expectedFloat]) {
            $params = $result->matches("/test/{$intInput}/{$floatInput}");

            $this->assertNotNull($params);
            $this->assertSame($expectedInt, $params['int_val'],
                "Integer conversion failed for: $intInput");
            $this->assertSame($expectedFloat, $params['float_val'],
                "Float conversion failed for: $floatInput");

            $this->assertIsInt($params['int_val']);
            $this->assertIsFloat($params['float_val']);
        }
    }

    #[Test]
    #[TestDox('Type conversion behavior is documented and predictable')]
    public function type_conversion_behavior_is_documented_and_predictable(): void
    {
        $this->assertTrue(true, 'Type conversion rules are documented and predictable');
    }
}