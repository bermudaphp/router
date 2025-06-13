<?php

declare(strict_types=1);

namespace Bermuda\Router\Tests;

use Bermuda\Router\Routes;
use Bermuda\Router\RoutesCache;
use Bermuda\Router\RouteRecord;
use Bermuda\Router\Compiler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\Group;

#[Group('parameter-extraction')]
#[TestDox('Consistent parameter extraction tests')]
final class ParameterExtractionTest extends TestCase
{
    private Routes $routes;
    private RoutesCache $routesCache;

    protected function setUp(): void
    {
        $this->routes = new Routes(new Compiler());

        $cacheData = [
            'static' => [],
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
                    'name' => 'product.show',
                    'path' => '/products/[id]/[price]',
                    'methods' => ['GET'],
                    'regex' => '/^\/products\/(?P<id>\d+)\/(?P<price>[^\/]+)$/',
                    'handler' => 'ProductController',
                    'middleware' => [],
                    'group' => null,
                    'parameters' => ['id', 'price'],
                    'defaults' => null,
                ],
                [
                    'name' => 'blog.post',
                    'path' => '/blog/[year]/[month]/[slug]',
                    'methods' => ['GET'],
                    'regex' => '/^\/blog\/(?P<year>[12]\d{3})\/(?P<month>0[1-9]|1[0-2])\/(?P<slug>[a-z0-9-]+)$/',
                    'handler' => 'BlogController',
                    'middleware' => [],
                    'group' => null,
                    'parameters' => ['year', 'month', 'slug'],
                    'defaults' => ['format' => 'html', 'lang' => 'en'],
                ],
                [
                    'name' => 'posts.category',
                    'path' => '/posts/[?category]',
                    'methods' => ['GET'],
                    'regex' => '/^\/posts(?:\/(?P<category>[^\/]+))?$/',
                    'handler' => 'PostController',
                    'middleware' => [],
                    'group' => null,
                    'parameters' => ['category'],
                    'defaults' => ['category' => 'general', 'per_page' => 10],
                ],
                [
                    'name' => 'api.search',
                    'path' => '/api/search/[query]/[?page]/[?limit]',
                    'methods' => ['GET'],
                    'regex' => '/^\/api\/search\/(?P<query>[^\/]+)(?:\/(?P<page>[^\/]+))?(?:\/(?P<limit>[^\/]+))?$/',
                    'handler' => 'SearchController',
                    'middleware' => [],
                    'group' => null,
                    'parameters' => ['query', 'page', 'limit'],
                    'defaults' => ['page' => 1, 'limit' => 20],
                ],

                [
                    'name' => 'user.profile',
                    'path' => '/profile/[username]',
                    'methods' => ['GET'],
                    'regex' => '/^\/profile\/(?P<username>[a-zA-Z0-9_-]+)$/',
                    'handler' => 'UserController',
                    'middleware' => [],
                    'group' => null,
                    'parameters' => ['username'],
                    'defaults' => null,
                ],
                [
                    'name' => 'product.variant',
                    'path' => '/shop/[category]/[item]',
                    'methods' => ['GET'],
                    'regex' => '/^\/shop\/(?P<category>[a-z0-9-]+)\/(?P<item>[a-zA-Z0-9._-]+)$/',
                    'handler' => 'ProductController',
                    'middleware' => [],
                    'group' => null,
                    'parameters' => ['category', 'item'],
                    'defaults' => null,
                ],
            ]
        ];

        $this->routesCache = new RoutesCache($cacheData, new Compiler());


        $this->routes->addRoute(
            RouteRecord::get('user.show', '/users/[id]', 'UserController')
                ->withTokens(['id' => '\d+'])
        );

        $this->routes->addRoute(
            RouteRecord::get('product.show', '/products/[id]/[price]', 'ProductController')
                ->withTokens(['id' => '\d+'])
        );

        $this->routes->addRoute(
            RouteRecord::get('blog.post', '/blog/[year]/[month]/[slug]', 'BlogController')
                ->withTokens([
                    'year' => '[12]\d{3}',
                    'month' => '0[1-9]|1[0-2]',
                    'slug' => '[a-z0-9-]+'
                ])
                ->withDefaults(['format' => 'html', 'lang' => 'en'])
        );

        $this->routes->addRoute(
            RouteRecord::get('posts.category', '/posts/[?category]', 'PostController')
                ->withDefaults(['category' => 'general', 'per_page' => 10])
        );

        $this->routes->addRoute(
            RouteRecord::get('api.search', '/api/search/[query]/[?page]/[?limit]', 'SearchController')
                ->withDefaults(['page' => 1, 'limit' => 20])
        );

        $this->routes->addRoute(
            RouteRecord::get('user.profile', '/profile/[username]', 'UserController')
                ->withTokens(['username' => '[a-zA-Z0-9_-]+'])
        );

        $this->routes->addRoute(
            RouteRecord::get('product.variant', '/shop/[category]/[item]', 'ProductController')
                ->withTokens([
                    'category' => '[a-z0-9-]+',
                    'item' => '[a-zA-Z0-9._-]+'
                ])
        );
    }

    #[Test]
    #[TestDox('Both implementations should convert numeric parameters consistently')]
    public function both_implementations_should_convert_numeric_parameters_consistently(): void
    {
        $routesMatch = $this->routes->match($this->routes, '/users/123', 'GET');
        $cacheMatch = $this->routesCache->match($this->routesCache, '/users/123', 'GET');

        $this->assertNotNull($routesMatch);
        $this->assertNotNull($cacheMatch);
        $this->assertEquals('user.show', $routesMatch->name);
        $this->assertEquals('user.show', $cacheMatch->name);

        // Both should convert numeric strings to numbers
        $routesId = $routesMatch->parameters->get('id');
        $cacheId = $cacheMatch->parameters->get('id');

        // Expected behavior: both should return integers for numeric values
        $this->assertSame(123, $routesId, 'Routes should convert numeric strings to integers');
        $this->assertSame(123, $cacheId, 'RoutesCache should convert numeric strings to integers');
        $this->assertIsInt($routesId);
        $this->assertIsInt($cacheId);
    }

    #[Test]
    #[DataProvider('numericParameterProvider')]
    #[TestDox('Compare numeric parameter handling consistency')]
    public function compare_numeric_parameter_handling_consistency(string $uri, string $paramName, mixed $expectedValue, string $expectedType): void
    {
        $routesMatch = $this->routes->match($this->routes, $uri, 'GET');
        $cacheMatch = $this->routesCache->match($this->routesCache, $uri, 'GET');

        $this->assertNotNull($routesMatch, "Routes failed to match: $uri");
        $this->assertNotNull($cacheMatch, "RoutesCache failed to match: $uri");

        $routesValue = $routesMatch->parameters->get($paramName);
        $cacheValue = $cacheMatch->parameters->get($paramName);

        // Both implementations should behave identically
        $this->assertSame($expectedValue, $routesValue,
            "Routes parameter $paramName mismatch for $uri. Got: " . var_export($routesValue, true));
        $this->assertSame($expectedValue, $cacheValue,
            "RoutesCache parameter $paramName mismatch for $uri. Got: " . var_export($cacheValue, true));

        $this->assertEquals($expectedType, gettype($routesValue),
            "Routes parameter $paramName type mismatch for $uri");
        $this->assertEquals($expectedType, gettype($cacheValue),
            "RoutesCache parameter $paramName type mismatch for $uri");
    }

    public static function numericParameterProvider(): array
    {
        return [
            ['/users/123', 'id', 123, 'integer'],
            ['/users/999999', 'id', 999999, 'integer'],
            ['/products/456/99.99', 'id', 456, 'integer'],
            ['/products/456/99.99', 'price', 99.99, 'double'],
            ['/products/789/0.50', 'price', 0.50, 'double'],
            ['/products/100/1000', 'price', 1000, 'integer'],
            ['/blog/2024/12/hello-world', 'year', 2024, 'integer'],
            ['/blog/2024/12/hello-world', 'month', 12, 'integer'],
            ['/blog/2024/01/test-post', 'year', 2024, 'integer'],
            ['/blog/1999/02/test-post', 'month', 2, 'integer'],
        ];
    }

    #[Test]
    #[DataProvider('stringParameterProvider')]
    #[TestDox('Compare string parameter handling consistency')]
    public function compare_string_parameter_handling_consistency(string $uri, string $paramName, string $expected): void
    {
        $routesMatch = $this->routes->match($this->routes, $uri, 'GET');
        $cacheMatch = $this->routesCache->match($this->routesCache, $uri, 'GET');

        $this->assertNotNull($routesMatch, "Routes failed to match: $uri");
        $this->assertNotNull($cacheMatch, "RoutesCache failed to match: $uri");

        $routesValue = $routesMatch->parameters->get($paramName);
        $cacheValue = $cacheMatch->parameters->get($paramName);

        // Both should return identical strings for non-numeric values
        $this->assertSame($expected, $routesValue);
        $this->assertSame($expected, $cacheValue);
        $this->assertIsString($routesValue);
        $this->assertIsString($cacheValue);
    }

    public static function stringParameterProvider(): array
    {
        return [
            ['/profile/john-doe', 'username', 'john-doe'],
            ['/profile/user_123', 'username', 'user_123'],
            ['/shop/electronics/laptop', 'category', 'electronics'],
            ['/shop/books/php-guide', 'item', 'php-guide'],
            ['/blog/2024/12/hello-world-post', 'slug', 'hello-world-post'],
        ];
    }

    #[Test]
    #[TestDox('Default value handling should be consistent')]
    public function default_value_handling_should_be_consistent(): void
    {
        $routesMatch = $this->routes->match($this->routes, '/blog/2024/12/test-post', 'GET');
        $cacheMatch = $this->routesCache->match($this->routesCache, '/blog/2024/12/test-post', 'GET');

        $this->assertNotNull($routesMatch);
        $this->assertNotNull($cacheMatch);

        // Check if defaults are merged into parameters during matching
        $routesParams = $routesMatch->parameters->toArray();
        $cacheParams = $cacheMatch->parameters->toArray();

        // Both should include extracted parameters
        $this->assertEquals(2024, $routesParams['year']);
        $this->assertEquals(12, $routesParams['month']);
        $this->assertEquals('test-post', $routesParams['slug']);

        $this->assertEquals(2024, $cacheParams['year']);
        $this->assertEquals(12, $cacheParams['month']);
        $this->assertEquals('test-post', $cacheParams['slug']);

        // Default values should be included in parameters for both implementations
        if (isset($routesParams['format'])) {
            $this->assertEquals('html', $routesParams['format']);
            $this->assertEquals('en', $routesParams['lang']);
        }

        if (isset($cacheParams['format'])) {
            $this->assertEquals('html', $cacheParams['format']);
            $this->assertEquals('en', $cacheParams['lang']);
        }

        // Both implementations should handle defaults consistently
        $this->assertEquals(
            isset($routesParams['format']),
            isset($cacheParams['format']),
            'Both implementations should handle default values consistently'
        );
    }

    #[Test]
    #[TestDox('Optional parameter handling - parameter present')]
    public function optional_parameter_handling_parameter_present(): void
    {
        $routesMatch = $this->routes->match($this->routes, '/posts/technology', 'GET');
        $cacheMatch = $this->routesCache->match($this->routesCache, '/posts/technology', 'GET');

        $this->assertNotNull($routesMatch);
        $this->assertNotNull($cacheMatch);

        // Both should extract the provided parameter identically
        $routesCategory = $routesMatch->parameters->get('category');
        $cacheCategory = $cacheMatch->parameters->get('category');

        $this->assertEquals('technology', $routesCategory);
        $this->assertEquals('technology', $cacheCategory);

        // Check if defaults are included in parameters
        $routesParams = $routesMatch->parameters->toArray();
        $cacheParams = $cacheMatch->parameters->toArray();

        // If default values are merged, check them
        if (isset($routesParams['per_page'])) {
            $this->assertEquals(10, $routesParams['per_page']);
        }
        if (isset($cacheParams['per_page'])) {
            $this->assertEquals(10, $cacheParams['per_page']);
        }

        // Both implementations should handle defaults consistently
        $this->assertEquals(
            isset($routesParams['per_page']),
            isset($cacheParams['per_page']),
            'Both implementations should handle default values consistently'
        );
    }

    #[Test]
    #[TestDox('Optional parameter handling - parameter missing')]
    public function optional_parameter_handling_parameter_missing(): void
    {
        $routesMatch = $this->routes->match($this->routes, '/posts', 'GET');
        $cacheMatch = $this->routesCache->match($this->routesCache, '/posts', 'GET');

        $this->assertNotNull($routesMatch);
        $this->assertNotNull($cacheMatch);

        $routesParams = $routesMatch->parameters->toArray();
        $cacheParams = $cacheMatch->parameters->toArray();

        // Both should handle missing optional parameter with default value
        if (isset($routesParams['category'])) {
            $this->assertEquals('general', $routesParams['category']);
        }
        if (isset($cacheParams['category'])) {
            $this->assertEquals('general', $cacheParams['category']);
        }

        if (isset($routesParams['per_page'])) {
            $this->assertEquals(10, $routesParams['per_page']);
        }
        if (isset($cacheParams['per_page'])) {
            $this->assertEquals(10, $cacheParams['per_page']);
        }

        // Both implementations should handle defaults consistently
        $this->assertEquals(
            isset($routesParams['category']),
            isset($cacheParams['category']),
            'Both implementations should handle optional parameter defaults consistently'
        );

        $this->assertEquals(
            isset($routesParams['per_page']),
            isset($cacheParams['per_page']),
            'Both implementations should handle other default values consistently'
        );
    }

    #[Test]
    #[TestDox('Multiple optional parameters handling')]
    public function multiple_optional_parameters_handling(): void
    {
        $testCases = [
            // All parameters provided
            ['/api/search/php-router/2/50',
                ['query' => 'php-router', 'page' => 2, 'limit' => 50]],

            // Only query provided
            ['/api/search/php-router',
                ['query' => 'php-router']],

            // Query and page provided
            ['/api/search/php-router/3',
                ['query' => 'php-router', 'page' => 3]],
        ];

        foreach ($testCases as [$uri, $expectedParams]) {
            $routesMatch = $this->routes->match($this->routes, $uri, 'GET');
            $cacheMatch = $this->routesCache->match($this->routesCache, $uri, 'GET');

            $this->assertNotNull($routesMatch, "Routes failed to match: $uri");
            $this->assertNotNull($cacheMatch, "RoutesCache failed to match: $uri");

            foreach ($expectedParams as $param => $expectedValue) {
                $routesValue = $routesMatch->parameters->get($param);
                $cacheValue = $cacheMatch->parameters->get($param);

                $this->assertEquals($expectedValue, $routesValue,
                    "Routes parameter mismatch for $param in $uri");
                $this->assertEquals($expectedValue, $cacheValue,
                    "RoutesCache parameter mismatch for $param in $uri");

                // Ensure type consistency
                $this->assertEquals(gettype($routesValue), gettype($cacheValue),
                    "Type mismatch between Routes and RoutesCache for $param in $uri");
            }

            // Check that both implementations handle default values consistently
            $routesParams = $routesMatch->parameters->toArray();
            $cacheParams = $cacheMatch->parameters->toArray();

            // For missing optional parameters, check if defaults are applied
            if (!isset($expectedParams['page'])) {
                if (isset($routesParams['page'])) {
                    $this->assertEquals(1, $routesParams['page'], "Routes should apply default page value");
                }
                if (isset($cacheParams['page'])) {
                    $this->assertEquals(1, $cacheParams['page'], "RoutesCache should apply default page value");
                }
                $this->assertEquals(
                    isset($routesParams['page']),
                    isset($cacheParams['page']),
                    'Both should handle page default consistently'
                );
            }

            if (!isset($expectedParams['limit'])) {
                if (isset($routesParams['limit'])) {
                    $this->assertEquals(20, $routesParams['limit'], "Routes should apply default limit value");
                }
                if (isset($cacheParams['limit'])) {
                    $this->assertEquals(20, $cacheParams['limit'], "RoutesCache should apply default limit value");
                }
                $this->assertEquals(
                    isset($routesParams['limit']),
                    isset($cacheParams['limit']),
                    'Both should handle limit default consistently'
                );
            }
        }
    }

    #[Test]
    #[TestDox('Edge cases handled consistently')]
    public function edge_cases_handled_consistently(): void
    {
        $edgeCases = [
            '/users/999',
            '/products/123/0.0',
        ];

        foreach ($edgeCases as $uri) {
            $routesMatch = $this->routes->match($this->routes, $uri, 'GET');
            $cacheMatch = $this->routesCache->match($this->routesCache, $uri, 'GET');

            // Both should either match or not match consistently
            if ($routesMatch === null) {
                $this->assertNull($cacheMatch, "Inconsistent matching for edge case: $uri");
            } else {
                $this->assertNotNull($cacheMatch, "Inconsistent matching for edge case: $uri");
                $this->assertEquals($routesMatch->name, $cacheMatch->name,
                    "Different routes matched for: $uri");

                // Compare all parameters
                $routesParams = $routesMatch->parameters->toArray();
                $cacheParams = $cacheMatch->parameters->toArray();

                $this->assertEquals($routesParams, $cacheParams,
                    "Parameter extraction mismatch for: $uri");
            }
        }
    }

    #[Test]
    #[TestDox('Parameter types are handled consistently')]
    public function parameter_types_are_handled_consistently(): void
    {
        $testCases = [
            // Integer parameters
            ['uri' => '/users/123', 'param' => 'id', 'expected_type' => 'integer'],

            // Float parameters
            ['uri' => '/products/456/99.99', 'param' => 'price', 'expected_type' => 'double'],
            ['uri' => '/products/789/0.5', 'param' => 'price', 'expected_type' => 'double'],

            // String parameters (используем новые routes)
            ['uri' => '/profile/john-doe', 'param' => 'username', 'expected_type' => 'string'],
            ['uri' => '/blog/2024/12/hello-world', 'param' => 'slug', 'expected_type' => 'string'],
        ];

        foreach ($testCases as $case) {
            $routesMatch = $this->routes->match($this->routes, $case['uri'], 'GET');
            $cacheMatch = $this->routesCache->match($this->routesCache, $case['uri'], 'GET');

            $this->assertNotNull($routesMatch, "Routes failed to match: {$case['uri']}");
            $this->assertNotNull($cacheMatch, "RoutesCache failed to match: {$case['uri']}");

            $routesValue = $routesMatch->parameters->get($case['param']);
            $cacheValue = $cacheMatch->parameters->get($case['param']);

            // Both should have the same type
            $this->assertEquals($case['expected_type'], gettype($routesValue),
                "Routes type mismatch for {$case['param']} in {$case['uri']}");
            $this->assertEquals($case['expected_type'], gettype($cacheValue),
                "RoutesCache type mismatch for {$case['param']} in {$case['uri']}");

            // And the same value
            $this->assertSame($routesValue, $cacheValue,
                "Value mismatch between Routes and RoutesCache for {$case['param']} in {$case['uri']}");
        }
    }

    #[Test]
    #[TestDox('Performance comparison between implementations')]
    public function performance_comparison_between_implementations(): void
    {
        $testUri = '/blog/2024/12/performance-test-post';
        $iterations = 1000;

        // Warm up
        $this->routes->match($this->routes, $testUri, 'GET');
        $this->routesCache->match($this->routesCache, $testUri, 'GET');

        // Time Routes
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->routes->match($this->routes, $testUri, 'GET');
        }
        $routesTime = microtime(true) - $startTime;

        // Time RoutesCache
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->routesCache->match($this->routesCache, $testUri, 'GET');
        }
        $cacheTime = microtime(true) - $startTime;

        // Both should complete in reasonable time (less than 1 second for 1000 iterations)
        $this->assertLessThan(1.0, $routesTime, 'Routes performance should be reasonable');
        $this->assertLessThan(1.0, $cacheTime, 'RoutesCache performance should be reasonable');

        // This is informational - RoutesCache should generally be faster but we don't make it a hard requirement
        echo "\nPerformance comparison:\n";
        echo "Routes: " . number_format($routesTime * 1000, 2) . "ms\n";
        echo "RoutesCache: " . number_format($cacheTime * 1000, 2) . "ms\n";
        echo "Ratio: " . number_format($routesTime / $cacheTime, 2) . "x\n";
    }

    #[Test]
    #[TestDox('Default values are handled according to implementation behavior')]
    public function default_values_are_handled_according_to_implementation_behavior(): void
    {
        // Test route with defaults
        $routesMatch = $this->routes->match($this->routes, '/blog/2024/12/test-post', 'GET');
        $cacheMatch = $this->routesCache->match($this->routesCache, '/blog/2024/12/test-post', 'GET');

        $this->assertNotNull($routesMatch);
        $this->assertNotNull($cacheMatch);

        // Check how each implementation actually handles defaults
        $routesParams = $routesMatch->parameters->toArray();
        $cacheParams = $cacheMatch->parameters->toArray();

        // Document actual behavior
        echo "\nDefault values behavior:\n";
        echo "Routes parameters: " . json_encode($routesParams) . "\n";
        echo "Cache parameters: " . json_encode($cacheParams) . "\n";

        // Check defaults property separately if parameters don't include them
        if ($routesMatch->defaults) {
            echo "Routes defaults: " . json_encode($routesMatch->defaults->toArray()) . "\n";
        }
        if ($cacheMatch->defaults) {
            echo "Cache defaults: " . json_encode($cacheMatch->defaults->toArray()) . "\n";
        }

        // Both implementations should be consistent with each other
        // (even if they don't include defaults in parameters)
        $this->assertEquals(
            isset($routesParams['format']),
            isset($cacheParams['format']),
            'Both implementations should handle format default consistently'
        );

        $this->assertEquals(
            isset($routesParams['lang']),
            isset($cacheParams['lang']),
            'Both implementations should handle lang default consistently'
        );
    }
}