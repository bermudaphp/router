<?php

declare(strict_types=1);

namespace Bermuda\Router\Tests;

use Bermuda\Router\RouteCompileResult;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\Group;

#[Group('route-compile-result')]
#[TestDox('RouteCompileResult tests')]
final class RouteCompileResultTest extends TestCase
{
    #[Test]
    #[TestDox('Can create RouteCompileResult with basic parameters')]
    public function can_create_route_compile_result_with_basic_parameters(): void
    {
        $regex = '/^\/users\/(?P<id>[^\/]+)$/';
        $parameters = ['id'];
        $optionalParameters = [];

        $result = new RouteCompileResult($regex, $parameters, $optionalParameters);

        $this->assertEquals($regex, $result->regex);
        $this->assertEquals($parameters, $result->parameters);
        $this->assertEquals($optionalParameters, $result->optionalParameters);
    }

    #[Test]
    #[TestDox('Can create RouteCompileResult with optional parameters')]
    public function can_create_route_compile_result_with_optional_parameters(): void
    {
        $regex = '/^\/posts\/(?P<category>[^\/]+)(?:\/(?P<slug>[^\/]+))?$/';
        $parameters = ['category', 'slug'];
        $optionalParameters = ['slug'];

        $result = new RouteCompileResult($regex, $parameters, $optionalParameters);

        $this->assertEquals($regex, $result->regex);
        $this->assertEquals($parameters, $result->parameters);
        $this->assertEquals($optionalParameters, $result->optionalParameters);
    }

    #[Test]
    #[TestDox('matches() returns parameters for matching URL')]
    public function matches_returns_parameters_for_matching_url(): void
    {
        $result = new RouteCompileResult(
            '/^\/users\/(?P<id>[^\/]+)$/',
            ['id'],
            []
        );

        $params = $result->matches('/users/123');

        $this->assertNotNull($params);
        $this->assertEquals(['id' => 123], $params);
    }

    #[Test]
    #[TestDox('matches() returns null for non-matching URL')]
    public function matches_returns_null_for_non_matching_url(): void
    {
        $result = new RouteCompileResult(
            '/^\/users\/(?P<id>[^\/]+)$/',
            ['id'],
            []
        );

        $params = $result->matches('/posts/123');

        $this->assertNull($params);
    }

    #[Test]
    #[TestDox('matches() handles multiple parameters with type conversion')]
    public function matches_handles_multiple_parameters_with_type_conversion(): void
    {
        $result = new RouteCompileResult(
            '/^\/products\/(?P<id>[^\/]+)\/(?P<price>[^\/]+)\/(?P<name>[^\/]+)$/',
            ['id', 'price', 'name'],
            []
        );

        $params = $result->matches('/products/123/99.99/laptop');

        $this->assertNotNull($params);
        $this->assertEquals([
            'id' => 123,
            'price' => 99.99,
            'name' => 'laptop'
        ], $params);
    }

    #[Test]
    #[TestDox('matches() handles optional parameters')]
    public function matches_handles_optional_parameters(): void
    {
        $result = new RouteCompileResult(
            '/^\/posts\/(?P<category>[^\/]+)(?:\/(?P<slug>[^\/]+))?$/',
            ['category', 'slug'],
            ['slug']
        );

        $params1 = $result->matches('/posts/tech/hello-world');
        $this->assertNotNull($params1);
        $this->assertEquals(['category' => 'tech', 'slug' => 'hello-world'], $params1);

        $params2 = $result->matches('/posts/tech');
        $this->assertNotNull($params2);
        $this->assertEquals(['category' => 'tech', 'slug' => null], $params2);
    }

    #[Test]
    #[TestDox('matches() applies default values for missing parameters')]
    public function matches_applies_default_values_for_missing_parameters(): void
    {
        $result = new RouteCompileResult(
            '/^\/posts\/(?P<category>[^\/]+)(?:\/(?P<slug>[^\/]+))?$/',
            ['category', 'slug'],
            ['slug']
        );

        $defaults = ['slug' => 'default-slug'];
        $params = $result->matches('/posts/tech', $defaults);

        $this->assertNotNull($params);
        $this->assertEquals(['category' => 'tech', 'slug' => 'default-slug'], $params);
    }

    #[Test]
    #[TestDox('matches() preserves non-numeric strings')]
    public function matches_preserves_non_numeric_strings(): void
    {
        $result = new RouteCompileResult(
            '/^\/mixed\/(?P<code>[^\/]+)\/(?P<name>[^\/]+)$/',
            ['code', 'name'],
            []
        );

        $params = $result->matches('/mixed/abc123/hello-world');

        $this->assertNotNull($params);
        $this->assertEquals([
            'code' => 'abc123',
            'name' => 'hello-world'
        ], $params);
    }

    #[Test]
    #[TestDox('matches() handles scientific notation')]
    public function matches_handles_scientific_notation(): void
    {
        $result = new RouteCompileResult(
            '/^\/science\/(?P<value>[^\/]+)$/',
            ['value'],
            []
        );

        $params = $result->matches('/science/1e5');

        $this->assertNotNull($params);
        $this->assertEquals(['value' => 100000.0], $params);
    }

    #[Test]
    #[TestDox('matches() handles negative numbers')]
    public function matches_handles_negative_numbers(): void
    {
        $result = new RouteCompileResult(
            '/^\/numbers\/(?P<int>[^\/]+)\/(?P<float>[^\/]+)$/',
            ['int', 'float'],
            []
        );

        $params = $result->matches('/numbers/-123/-45.67');

        $this->assertNotNull($params);
        $this->assertEquals([
            'int' => -123,
            'float' => -45.67
        ], $params);
    }

    #[Test]
    #[TestDox('matches() handles zero values')]
    public function matches_handles_zero_values(): void
    {
        $result = new RouteCompileResult(
            '/^\/zeros\/(?P<int>[^\/]+)\/(?P<float>[^\/]+)$/',
            ['int', 'float'],
            []
        );

        $params = $result->matches('/zeros/0/0.0');

        $this->assertNotNull($params);
        $this->assertEquals([
            'int' => 0,
            'float' => 0.0
        ], $params);
    }

    #[Test]
    #[TestDox('isParametrized() returns true for routes with parameters')]
    public function is_parametrized_returns_true_for_routes_with_parameters(): void
    {
        $result = new RouteCompileResult(
            '/^\/users\/(?P<id>[^\/]+)$/',
            ['id'],
            []
        );

        $this->assertTrue($result->isParametrized());
    }

    #[Test]
    #[TestDox('isParametrized() returns false for routes without parameters')]
    public function is_parametrized_returns_false_for_routes_without_parameters(): void
    {
        $result = new RouteCompileResult(
            '/^\/health$/',
            [],
            []
        );

        $this->assertFalse($result->isParametrized());
    }

    #[Test]
    #[TestDox('testMatch() returns true for matching URL without parameter extraction')]
    public function test_match_returns_true_for_matching_url_without_parameter_extraction(): void
    {
        $result = new RouteCompileResult(
            '/^\/users\/(?P<id>[^\/]+)$/',
            ['id'],
            []
        );

        $this->assertTrue($result->testMatch('/users/123'));
        $this->assertFalse($result->testMatch('/posts/123'));
    }

    #[Test]
    #[DataProvider('numericConversionProvider')]
    #[TestDox('matches() performs correct numeric type conversion')]
    public function matches_performs_correct_numeric_type_conversion(
        string $url,
        string $paramName,
        mixed $expectedValue,
        string $expectedType
    ): void {
        $result = new RouteCompileResult(
            '/^\/test\/(?P<param>[^\/]+)$/',
            ['param'],
            []
        );

        $params = $result->matches($url);

        $this->assertNotNull($params);
        $this->assertSame($expectedValue, $params[$paramName]);
        $this->assertEquals($expectedType, gettype($params[$paramName]));
    }

    public static function numericConversionProvider(): array
    {
        return [
            ['/test/123', 'param', 123, 'integer'],
            ['/test/0', 'param', 0, 'integer'],
            ['/test/-456', 'param', -456, 'integer'],
            ['/test/78.90', 'param', 78.90, 'double'],
            ['/test/0.0', 'param', 0.0, 'double'],
            ['/test/-12.34', 'param', -12.34, 'double'],
            ['/test/1e5', 'param', 100000.0, 'double'],
            ['/test/2.5e-3', 'param', 0.0025, 'double'],
            ['/test/hello', 'param', 'hello', 'string'],
            ['/test/123abc', 'param', '123abc', 'string'],
            ['/test/abc123', 'param', 'abc123', 'string'],
        ];
    }

    #[Test]
    #[TestDox('matches() handles complex route patterns')]
    public function matches_handles_complex_route_patterns(): void
    {
        $result = new RouteCompileResult(
            '/^\/api\/(?P<version>[^\/]+)\/users\/(?P<id>[^\/]+)\/posts\/(?P<slug>[^\/]+)(?:\/(?P<format>[^\/]+))?$/',
            ['version', 'id', 'slug', 'format'],
            ['format']
        );

        $params1 = $result->matches('/api/v1/users/123/posts/hello-world/json');
        $this->assertNotNull($params1);
        $this->assertEquals([
            'version' => 'v1',
            'id' => 123,
            'slug' => 'hello-world',
            'format' => 'json'
        ], $params1);

        $params2 = $result->matches('/api/v2/users/456/posts/another-post');
        $this->assertNotNull($params2);
        $this->assertEquals([
            'version' => 'v2',
            'id' => 456,
            'slug' => 'another-post',
            'format' => null
        ], $params2);
    }

    #[Test]
    #[TestDox('matches() handles empty parameter values')]
    public function matches_handles_empty_parameter_values(): void
    {
        $result = new RouteCompileResult(
            '/^\/test\/(?P<param>[^\/]*)$/',
            ['param'],
            []
        );

        $params = $result->matches('/test/');

        $this->assertNotNull($params);
        $this->assertEquals(['param' => ''], $params);
    }

    #[Test]
    #[TestDox('matches() performance is acceptable for many operations')]
    public function matches_performance_is_acceptable_for_many_operations(): void
    {
        $result = new RouteCompileResult(
            '/^\/api\/(?P<version>[^\/]+)\/users\/(?P<id>[^\/]+)\/posts\/(?P<slug>[^\/]+)$/',
            ['version', 'id', 'slug'],
            []
        );

        $startTime = microtime(true);

        for ($i = 0; $i < 10000; $i++) {
            $params = $result->matches('/api/v1/users/123/posts/test-post');
            $this->assertNotNull($params);
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $this->assertLessThan(1.0, $duration,
            'RouteCompileResult should handle 10,000 matches quickly');
    }

    #[Test]
    #[TestDox('testMatch() is faster than matches() for boolean checks')]
    public function test_match_is_faster_than_matches_for_boolean_checks(): void
    {
        $result = new RouteCompileResult(
            '/^\/api\/(?P<version>[^\/]+)\/users\/(?P<id>[^\/]+)\/posts\/(?P<slug>[^\/]+)$/',
            ['version', 'id', 'slug'],
            []
        );

        $iterations = 50000;
        $url = '/api/v1/users/123/posts/test-post';

        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $result->testMatch($url);
        }
        $testMatchTime = microtime(true) - $startTime;

        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $result->matches($url);
        }
        $matchesTime = microtime(true) - $startTime;

        $this->assertLessThan($matchesTime, $testMatchTime,
            'testMatch() should be faster than matches() for boolean checks');

        echo "\nPerformance comparison for $iterations iterations:\n";
        echo "testMatch(): " . number_format($testMatchTime * 1000, 2) . "ms\n";
        echo "matches(): " . number_format($matchesTime * 1000, 2) . "ms\n";
    }

    #[Test]
    #[TestDox('Default values override null parameter values')]
    public function default_values_override_null_parameter_values(): void
    {
        $result = new RouteCompileResult(
            '/^\/posts(?:\/(?P<category>[^\/]+))?(?:\/(?P<page>[^\/]+))?$/',
            ['category', 'page'],
            ['category', 'page']
        );

        $defaults = ['category' => 'general', 'page' => 1];

        $params = $result->matches('/posts', $defaults);
        $this->assertNotNull($params);
        $this->assertEquals(['category' => 'general', 'page' => 1], $params);

        $params = $result->matches('/posts/tech', $defaults);
        $this->assertNotNull($params);
        $this->assertEquals(['category' => 'tech', 'page' => 1], $params);

        $params = $result->matches('/posts/tech/2', $defaults);
        $this->assertNotNull($params);
        $this->assertEquals(['category' => 'tech', 'page' => 2], $params);
    }
}
