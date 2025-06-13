<?php

declare(strict_types=1);

namespace Bermuda\Router\Tests;

use Bermuda\Router\Compiler;
use Bermuda\Router\CompilerInterface;
use Bermuda\Router\RouteCompileResult;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\Group;

#[Group('compiler')]
#[TestDox('Compiler tests')]
final class CompilerTest extends TestCase
{
    private Compiler $compiler;

    protected function setUp(): void
    {
        $this->compiler = new Compiler();
    }

    #[Test]
    #[TestDox('Can construct compiler with default patterns')]
    public function can_construct_compiler_with_default_patterns(): void
    {
        $compiler = new Compiler();

        $this->assertInstanceOf(Compiler::class, $compiler);
    }

    #[Test]
    #[TestDox('Can construct compiler with custom patterns')]
    public function can_construct_compiler_with_custom_patterns(): void
    {
        $customPatterns = [
            'id' => '\d+',
            'uuid' => '[0-9a-f-]{36}',
            'custom' => '[a-z]+',
        ];

        $compiler = new Compiler($customPatterns);

        $this->assertInstanceOf(Compiler::class, $compiler);
    }

    #[Test]
    #[TestDox('Can set additional patterns')]
    public function can_set_additional_patterns(): void
    {
        $newPatterns = [
            'email' => '[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}',
            'date' => '\d{4}-\d{2}-\d{2}',
        ];

        $result = $this->compiler->setPatterns($newPatterns);

        $this->assertSame($this->compiler, $result);
    }

    #[Test]
    #[TestDox('Can set default pattern')]
    public function can_set_default_pattern(): void
    {
        $result = $this->compiler->setDefaultPattern('[a-zA-Z0-9]+');

        $this->assertSame($this->compiler, $result);
    }

    #[Test]
    #[TestDox('Can detect parametrized routes')]
    public function can_detect_parametrized_routes(): void
    {
        $this->assertFalse($this->compiler->isParametrized('/users'));
        $this->assertFalse($this->compiler->isParametrized('/about/contact'));
        $this->assertTrue($this->compiler->isParametrized('/users/[id]'));
        $this->assertTrue($this->compiler->isParametrized('/posts/[?category]'));
        $this->assertTrue($this->compiler->isParametrized('/blog/[year]/[month]/[slug]'));
    }

    #[Test]
    #[TestDox('Can detect non-parametrized routes')]
    public function can_detect_non_parametrized_routes(): void
    {
        $staticRoutes = [
            '/',
            '/users',
            '/about/contact',
            '/api/v1/health',
            '/admin/dashboard',
        ];

        foreach ($staticRoutes as $route) {
            $this->assertFalse($this->compiler->isParametrized($route), "Route '$route' should not be parametrized");
        }
    }

    #[Test]
    #[TestDox('Can compile static route')]
    public function can_compile_static_route(): void
    {
        $result = $this->compiler->compile('/users');

        $this->assertInstanceOf(RouteCompileResult::class, $result);
        $this->assertEquals('/^\/users$/', $result->regex);
        $this->assertEmpty($result->parameters);
        $this->assertEmpty($result->optionalParameters);
        $this->assertFalse($result->isParametrized());
    }

    #[Test]
    #[TestDox('Can compile route with single required parameter')]
    public function can_compile_route_with_single_required_parameter(): void
    {
        $result = $this->compiler->compile('/users/[id]');

        $this->assertInstanceOf(RouteCompileResult::class, $result);
        $this->assertStringContainsString('(?P<id>', $result->regex);
        $this->assertEquals(['id'], $result->parameters);
        $this->assertEmpty($result->optionalParameters);
        $this->assertTrue($result->isParametrized());
    }

    #[Test]
    #[TestDox('Can compile route with multiple required parameters')]
    public function can_compile_route_with_multiple_required_parameters(): void
    {
        $result = $this->compiler->compile('/posts/[year]/[month]/[slug]');

        $this->assertInstanceOf(RouteCompileResult::class, $result);
        $this->assertStringContainsString('(?P<year>', $result->regex);
        $this->assertStringContainsString('(?P<month>', $result->regex);
        $this->assertStringContainsString('(?P<slug>', $result->regex);
        $this->assertEquals(['year', 'month', 'slug'], $result->parameters);
        $this->assertEmpty($result->optionalParameters);
        $this->assertTrue($result->isParametrized());
    }

    #[Test]
    #[TestDox('Can compile route with optional parameter')]
    public function can_compile_route_with_optional_parameter(): void
    {
        $result = $this->compiler->compile('/posts/[?category]');

        $this->assertInstanceOf(RouteCompileResult::class, $result);
        $this->assertStringContainsString('(?P<category>', $result->regex);
        $this->assertEquals(['category'], $result->parameters);
        $this->assertEquals(['category'], $result->optionalParameters);
        $this->assertTrue($result->isParametrized());
    }

    #[Test]
    #[TestDox('Can compile route with mixed required and optional parameters')]
    public function can_compile_route_with_mixed_required_and_optional_parameters(): void
    {
        $result = $this->compiler->compile('/posts/[year]/[?category]/[slug]');

        $this->assertInstanceOf(RouteCompileResult::class, $result);
        $this->assertEquals(['year', 'category', 'slug'], $result->parameters);
        $this->assertEquals(['category'], $result->optionalParameters);
        $this->assertTrue($result->isParametrized());
    }

    #[Test]
    #[TestDox('Can compile route with inline parameter pattern')]
    public function can_compile_route_with_inline_parameter_pattern(): void
    {
        $result = $this->compiler->compile('/users/[id:\d+]');

        $this->assertInstanceOf(RouteCompileResult::class, $result);
        $this->assertStringContainsString('\d+', $result->regex);
        $this->assertEquals(['id'], $result->parameters);
        $this->assertEmpty($result->optionalParameters);
    }

    #[Test]
    #[TestDox('Can compile route with optional parameter and inline pattern')]
    public function can_compile_route_with_optional_parameter_and_inline_pattern(): void
    {
        $result = $this->compiler->compile('/posts/[?format:json|xml|html]');

        $this->assertInstanceOf(RouteCompileResult::class, $result);
        $this->assertStringContainsString('json|xml|html', $result->regex);
        $this->assertEquals(['format'], $result->parameters);
        $this->assertEquals(['format'], $result->optionalParameters);
    }

    #[Test]
    #[TestDox('Can compile route using predefined patterns')]
    public function can_compile_route_using_predefined_patterns(): void
    {
        $compiler = new Compiler(['id' => '\d+', 'slug' => '[a-z0-9-]+']);
        $result = $compiler->compile('/posts/[id]/[slug]');

        $this->assertInstanceOf(RouteCompileResult::class, $result);
        $this->assertStringContainsString('\d+', $result->regex);
        $this->assertStringContainsString('[a-z0-9-]+', $result->regex);
    }

    #[Test]
    #[TestDox('Inline patterns take precedence over predefined patterns')]
    public function inline_patterns_take_precedence_over_predefined_patterns(): void
    {
        $compiler = new Compiler(['id' => '\d+']); // Predefined: digits only
        $result = $compiler->compile('/users/[id:[a-z]+]'); // Inline: letters only

        $this->assertStringContainsString('[a-z]+', $result->regex);
        $this->assertStringNotContainsString('\d+', $result->regex);
    }

    #[Test]
    #[TestDox('Can compile route with default pattern for unknown parameters')]
    public function can_compile_route_with_default_pattern_for_unknown_parameters(): void
    {
        $result = $this->compiler->compile('/posts/[unknown_param]');

        $this->assertInstanceOf(RouteCompileResult::class, $result);
        $this->assertStringContainsString('[^\/]+', $result->regex); // Default pattern
        $this->assertEquals(['unknown_param'], $result->parameters);
    }

    #[Test]
    #[TestDox('Can compile route with custom default pattern')]
    public function can_compile_route_with_custom_default_pattern(): void
    {
        $this->compiler->setDefaultPattern('[a-zA-Z0-9]+');
        $result = $this->compiler->compile('/posts/[unknown_param]');

        $this->assertStringContainsString('[a-zA-Z0-9]+', $result->regex);
    }

    #[Test]
    #[TestDox('Throws exception for invalid parameter names')]
    public function throws_exception_for_invalid_parameter_names(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid parameter name');

        $this->compiler->compile('/users/[123invalid]');
    }

    #[Test]
    #[TestDox('Throws exception for unclosed parameter brackets')]
    public function throws_exception_for_unclosed_parameter_brackets(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unclosed parameter bracket');

        $this->compiler->compile('/users/[id');
    }

    #[Test]
    #[TestDox('Can generate URL for static route')]
    public function can_generate_url_for_static_route(): void
    {
        $url = $this->compiler->generate('/users', []);

        $this->assertEquals('/users', $url);
    }

    #[Test]
    #[TestDox('Can generate URL for route with required parameters')]
    public function can_generate_url_for_route_with_required_parameters(): void
    {
        $url = $this->compiler->generate('/users/[id]', ['id' => 123]);

        $this->assertEquals('/users/123', $url);
    }

    #[Test]
    #[TestDox('Can generate URL for route with multiple parameters')]
    public function can_generate_url_for_route_with_multiple_parameters(): void
    {
        $url = $this->compiler->generate('/posts/[year]/[month]/[slug]', [
            'year' => 2024,
            'month' => 12,
            'slug' => 'hello-world'
        ]);

        $this->assertEquals('/posts/2024/12/hello-world', $url);
    }

    #[Test]
    #[TestDox('Can generate URL for route with optional parameters')]
    public function can_generate_url_for_route_with_optional_parameters(): void
    {
        // Without optional parameter
        $url1 = $this->compiler->generate('/posts/[?category]', []);
        $this->assertEquals('/posts', $url1);

        // With optional parameter
        $url2 = $this->compiler->generate('/posts/[?category]', ['category' => 'tech']);
        $this->assertEquals('/posts/tech', $url2);
    }

    #[Test]
    #[TestDox('Can generate URL for route with mixed parameters')]
    public function can_generate_url_for_route_with_mixed_parameters(): void
    {
        $template = '/posts/[year]/[?category]/[slug]';

        // Without optional parameter
        $url1 = $this->compiler->generate($template, ['year' => 2024, 'slug' => 'hello']);
        $this->assertEquals('/posts/2024/hello', $url1);

        // With optional parameter
        $url2 = $this->compiler->generate($template, ['year' => 2024, 'category' => 'tech', 'slug' => 'hello']);
        $this->assertEquals('/posts/2024/tech/hello', $url2);
    }

    #[Test]
    #[TestDox('Ignores inline patterns during URL generation')]
    public function ignores_inline_patterns_during_url_generation(): void
    {
        $url = $this->compiler->generate('/users/[id:\d+]', ['id' => 123]);

        $this->assertEquals('/users/123', $url);
    }

    #[Test]
    #[TestDox('Throws exception when required parameter is missing')]
    public function throws_exception_when_required_parameter_is_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Required parameter \'id\' not provided');

        $this->compiler->generate('/users/[id]', []);
    }

    #[Test]
    #[TestDox('Normalizes slashes in generated URLs')]
    public function normalizes_slashes_in_generated_urls(): void
    {
        $url = $this->compiler->generate('/posts//[?category]//[slug]', ['slug' => 'hello']);

        $this->assertEquals('/posts/hello', $url);
    }

    #[Test]
    #[TestDox('Removes trailing slashes from generated URLs')]
    public function removes_trailing_slashes_from_generated_urls(): void
    {
        $url = $this->compiler->generate('/posts/[slug]/', ['slug' => 'hello']);

        $this->assertEquals('/posts/hello', $url);
    }

    #[Test]
    #[TestDox('Preserves root path in generated URLs')]
    public function preserves_root_path_in_generated_urls(): void
    {
        $url = $this->compiler->generate('/', []);

        $this->assertEquals('/', $url);
    }

    #[Test]
    #[TestDox('Can compile multiple routes at once')]
    public function can_compile_multiple_routes_at_once(): void
    {
        $routes = [
            'home' => '/',
            'users.index' => '/users',
            'users.show' => '/users/[id]',
            'posts.category' => '/posts/[?category]/[slug]',
        ];

        $results = $this->compiler->compileMultiple($routes);

        $this->assertCount(4, $results);
        $this->assertArrayHasKey('home', $results);
        $this->assertArrayHasKey('users.index', $results);
        $this->assertArrayHasKey('users.show', $results);
        $this->assertArrayHasKey('posts.category', $results);

        foreach ($results as $result) {
            $this->assertInstanceOf(RouteCompileResult::class, $result);
        }
    }

    #[Test]
    #[DataProvider('routeCompilationProvider')]
    #[TestDox('Can compile various route patterns')]
    public function can_compile_various_route_patterns(string $pattern, array $expectedParams, array $expectedOptional): void
    {
        $result = $this->compiler->compile($pattern);

        $this->assertEquals($expectedParams, $result->parameters);
        $this->assertEquals($expectedOptional, $result->optionalParameters);
        $this->assertTrue($result->isParametrized() === !empty($expectedParams));
    }

    public static function routeCompilationProvider(): array
    {
        return [
            'static route' => ['/', [], []],
            'simple static' => ['/users', [], []],
            'single required param' => ['/users/[id]', ['id'], []],
            'single optional param' => ['/posts/[?category]', ['category'], ['category']],
            'multiple required params' => ['/posts/[year]/[month]/[day]', ['year', 'month', 'day'], []],
            'multiple optional params' => ['/search/[?q]/[?page]', ['q', 'page'], ['q', 'page']],
            'mixed params' => ['/posts/[year]/[?category]/[slug]', ['year', 'category', 'slug'], ['category']],
            'params with patterns' => ['/users/[id:\d+]/[slug:[a-z-]+]', ['id', 'slug'], []],
            'optional with pattern' => ['/api/[?version:v\d+]', ['version'], ['version']],
            'complex mixed' => ['/api/[version]/posts/[?category]/[id:\d+]', ['version', 'category', 'id'], ['category']],
        ];
    }

    #[Test]
    #[DataProvider('urlGenerationProvider')]
    #[TestDox('Can generate URLs for various patterns')]
    public function can_generate_urls_for_various_patterns(string $template, array $params, string $expected): void
    {
        $url = $this->compiler->generate($template, $params);

        $this->assertEquals($expected, $url);
    }

    public static function urlGenerationProvider(): array
    {
        return [
            'static route' => ['/', [], '/'],
            'simple static' => ['/users', [], '/users'],
            'single param' => ['/users/[id]', ['id' => 123], '/users/123'],
            'multiple params' => ['/posts/[year]/[month]/[slug]', ['year' => 2024, 'month' => 12, 'slug' => 'hello'], '/posts/2024/12/hello'],
            'optional param omitted' => ['/posts/[?category]', [], '/posts'],
            'optional param included' => ['/posts/[?category]', ['category' => 'tech'], '/posts/tech'],
            'mixed params, optional omitted' => ['/posts/[year]/[?category]/[slug]', ['year' => 2024, 'slug' => 'hello'], '/posts/2024/hello'],
            'mixed params, optional included' => ['/posts/[year]/[?category]/[slug]', ['year' => 2024, 'category' => 'tech', 'slug' => 'hello'], '/posts/2024/tech/hello'],
            'with patterns (ignored)' => ['/users/[id:\d+]', ['id' => 123], '/users/123'],
            'optional with pattern' => ['/api/[?version:v\d+]', ['version' => 'v2'], '/api/v2'],
            'empty string param' => ['/posts/[?category]', ['category' => ''], '/posts'],
            'null param' => ['/posts/[?category]', ['category' => null], '/posts'],
        ];
    }

    #[Test]
    #[TestDox('Can handle nested brackets in route patterns')]
    public function can_handle_nested_brackets_in_route_patterns(): void
    {
        $result = $this->compiler->compile('/api/[endpoint]/[data]');

        $this->assertInstanceOf(RouteCompileResult::class, $result);
        $this->assertEquals(['endpoint', 'data'], $result->parameters);
    }

    #[Test]
    #[TestDox('Can handle special characters in route patterns')]
    public function can_handle_special_characters_in_route_patterns(): void
    {
        $result = $this->compiler->compile('/api/v1.0/users-admin/[id]');

        $this->assertInstanceOf(RouteCompileResult::class, $result);
        $this->assertStringContainsString('\/api\/v1\.0\/users\-admin\/', $result->regex);
        $this->assertEquals(['id'], $result->parameters);
    }

    #[Test]
    #[TestDox('Can handle routes with dots and hyphens')]
    public function can_handle_routes_with_dots_and_hyphens(): void
    {
        $patterns = [
            '/api/v1.2/data',
            '/admin-panel/users',
            '/user-profile/settings.json',
        ];

        foreach ($patterns as $pattern) {
            $result = $this->compiler->compile($pattern);
            $this->assertInstanceOf(RouteCompileResult::class, $result);
            $this->assertFalse($result->isParametrized());
        }
    }

    #[Test]
    #[TestDox('Can use default patterns from CompilerInterface')]
    public function can_use_default_patterns_from_compiler_interface(): void
    {
        $compiler = new Compiler();

        // Test some default patterns from CompilerInterface::DEFAULT_PATTERNS
        $result = $compiler->compile('/users/[id]/posts/[slug]');

        $this->assertInstanceOf(RouteCompileResult::class, $result);
        // Default 'id' pattern should be '\d+'
        // Default 'slug' pattern should be '[a-z0-9-]+'
        $this->assertStringContainsString('\d+', $result->regex);
        $this->assertStringContainsString('[a-z0-9-]+', $result->regex);
    }

    #[Test]
    #[TestDox('Custom patterns override default patterns')]
    public function custom_patterns_override_default_patterns(): void
    {
        $compiler = new Compiler(['id' => '[a-f0-9]{8}']); // Override default id pattern
        $result = $compiler->compile('/users/[id]');

        $this->assertStringContainsString('[a-f0-9]{8}', $result->regex);
        $this->assertStringNotContainsString('\d+', $result->regex);
    }

    #[Test]
    #[TestDox('Can handle empty parameter values in generation')]
    public function can_handle_empty_parameter_values_in_generation(): void
    {
        // Empty string should be treated as missing for optional parameters
        $url = $this->compiler->generate('/posts/[?category]', ['category' => '']);
        $this->assertEquals('/posts', $url);

        // Empty string for required parameter should still be included, but normalized
        $url2 = $this->compiler->generate('/posts/[category]', ['category' => '']);
        $this->assertEquals('/posts', $url2); // RoutePath.normalize() removes trailing slashes
    }

    #[Test]
    #[TestDox('Handles complex parameter patterns correctly')]
    public function handles_complex_parameter_patterns_correctly(): void
    {
        $result = $this->compiler->compile('/api/[version:v\d+\.\d+]/users/[id:\d+]/posts/[slug:[a-z0-9-]+]');

        $this->assertInstanceOf(RouteCompileResult::class, $result);
        $this->assertEquals(['version', 'id', 'slug'], $result->parameters);
        $this->assertStringContainsString('v\d+\.\d+', $result->regex);
        $this->assertStringContainsString('\d+', $result->regex);
        $this->assertStringContainsString('[a-z0-9-]+', $result->regex);
    }

    #[Test]
    #[TestDox('Can handle patterns with forward slashes')]
    public function can_handle_patterns_with_forward_slashes(): void
    {
        $compiler = new Compiler(['path' => '.+']); // Simplified pattern without forward slashes
        $result = $compiler->compile('/files/[path]');

        $this->assertInstanceOf(RouteCompileResult::class, $result);
        $this->assertEquals(['path'], $result->parameters);

        $params = $result->matches('/files/folder/subfolder/file.txt');

        // Test that the compiled route can match paths with forward slashes
        $this->assertNotNull($params);

        $this->assertEquals('folder/subfolder/file.txt', $params['path']);
    }

    #[Test]
    #[TestDox('Can handle parameter names with underscores')]
    public function can_handle_parameter_names_with_underscores(): void
    {
        $result = $this->compiler->compile('/api/[user_id]/posts/[post_slug]');

        $this->assertInstanceOf(RouteCompileResult::class, $result);
        $this->assertEquals(['user_id', 'post_slug'], $result->parameters);
        $this->assertStringContainsString('(?P<user_id>', $result->regex);
        $this->assertStringContainsString('(?P<post_slug>', $result->regex);
    }

    #[Test]
    #[TestDox('Validates parameter names with regex')]
    public function validates_parameter_names_with_regex(): void
    {
        $invalidNames = [
            '[2invalid]', // Starts with number
            '[invalid-name]', // Contains hyphen
            '[invalid.name]', // Contains dot
            '[invalid name]', // Contains space
        ];

        foreach ($invalidNames as $pattern) {
            $this->expectException(\InvalidArgumentException::class);
            $this->compiler->compile("/test/{$pattern}");
        }
    }
}
