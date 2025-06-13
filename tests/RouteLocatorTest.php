<?php

declare(strict_types=1);

namespace Bermuda\Router\Tests;

use Bermuda\Router\Locator\RouteLocator;
use Bermuda\Router\Routes;
use Bermuda\Router\RoutesCache;
use Bermuda\Router\RouteMap;
use Bermuda\Router\Compiler;
use Bermuda\Router\CompilerInterface;
use Bermuda\Router\Exception\RouterException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\Group;
use InvalidArgumentException;

#[Group('route-locator')]
#[TestDox('RouteLocator tests')]
final class RouteLocatorTest extends TestCase
{
    private string $tempDir;
    private string $tempFile;
    private CompilerInterface $compiler;

    protected function setUp(): void
    {
        $this->compiler = new Compiler();
        $this->tempDir = sys_get_temp_dir() . '/route_locator_tests_' . uniqid();
        mkdir($this->tempDir, 0777, true);
        $this->tempFile = $this->tempDir . '/routes.php';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    #[Test]
    #[TestDox('Can construct RouteLocator with required parameters')]
    public function can_construct_with_required_parameters(): void
    {
        $this->createSimpleRoutesFile();

        $locator = new RouteLocator($this->tempFile);

        $this->assertInstanceOf(RouteLocator::class, $locator);
        $this->assertEquals($this->tempFile, $locator->filename);
    }

    #[Test]
    #[TestDox('Can construct RouteLocator with all parameters')]
    public function can_construct_with_all_parameters(): void
    {
        $this->createSimpleRoutesFile();

        $context = ['service' => 'test'];
        $compiler = new Compiler(['id' => '\d+']);

        $locator = new RouteLocator(
            $this->tempFile,
            $context,
            $compiler,
            false,
            'customRoutes'
        );

        $this->assertInstanceOf(RouteLocator::class, $locator);
        $this->assertEquals($context, $locator->context);
        $this->assertSame($compiler, $locator->compiler);
        $this->assertFalse($locator->useCache);
    }

    #[Test]
    #[TestDox('Throws exception when file does not exist')]
    public function throws_exception_when_file_does_not_exist(): void
    {
        $nonExistentFile = '/path/that/does/not/exist.php';

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage("Route file: {$nonExistentFile} does not exist");

        new RouteLocator($nonExistentFile);
    }

    #[Test]
    #[TestDox('setFilename updates filename property')]
    public function set_filename_updates_filename_property(): void
    {
        $this->createSimpleRoutesFile();
        $locator = new RouteLocator($this->tempFile);

        $newFile = $this->tempDir . '/new_routes.php';
        $this->createSimpleRoutesFile($newFile);

        $result = $locator->setFilename($newFile);

        $this->assertSame($locator, $result);
        $this->assertEquals($newFile, $locator->filename);
    }

    #[Test]
    #[TestDox('setFilename throws exception for non-existent file')]
    public function set_filename_throws_exception_for_non_existent_file(): void
    {
        $this->createSimpleRoutesFile();
        $locator = new RouteLocator($this->tempFile);

        $nonExistentFile = '/does/not/exist.php';

        $this->expectException(RouterException::class);
        $this->expectExceptionMessage("Route file: {$nonExistentFile} does not exist");

        $locator->setFilename($nonExistentFile);
    }

    #[Test]
    #[TestDox('setContext updates context property')]
    public function set_context_updates_context_property(): void
    {
        $this->createSimpleRoutesFile();
        $locator = new RouteLocator($this->tempFile);

        $context = ['service' => 'test', 'config' => ['debug' => true]];
        $result = $locator->setContext($context);

        $this->assertSame($locator, $result);
        $this->assertEquals($context, $locator->context);
    }

    #[Test]
    #[TestDox('setCompiler updates compiler property')]
    public function set_compiler_updates_compiler_property(): void
    {
        $this->createSimpleRoutesFile();
        $locator = new RouteLocator($this->tempFile);

        $compiler = new Compiler(['slug' => '[a-z0-9-]+']);
        $result = $locator->setCompiler($compiler);

        $this->assertSame($locator, $result);
        $this->assertSame($compiler, $locator->compiler);
    }

    #[Test]
    #[TestDox('useCache updates useCache property')]
    public function use_cache_updates_use_cache_property(): void
    {
        $this->createSimpleRoutesFile();
        $locator = new RouteLocator($this->tempFile);

        // Default is true, set to false
        $result = $locator->useCache(false);
        $this->assertSame($locator, $result);
        $this->assertFalse($locator->useCache);

        // Set back to true
        $locator->useCache(true);
        $this->assertTrue($locator->useCache);
    }

    #[Test]
    #[TestDox('setRoutesVarName updates variable name')]
    public function set_routes_var_name_updates_variable_name(): void
    {
        $this->createSimpleRoutesFile();
        $locator = new RouteLocator($this->tempFile);

        $result = $locator->setRoutesVarName('myRoutes');

        $this->assertSame($locator, $result);
    }

    #[Test]
    #[TestDox('setRoutesVarName validates variable name format')]
    public function set_routes_var_name_validates_variable_name_format(): void
    {
        $this->createSimpleRoutesFile();
        $locator = new RouteLocator($this->tempFile);

        $invalidNames = [
            '123invalid',     // Starts with number
            'invalid-name',   // Contains hyphen
            'invalid.name',   // Contains dot
            'invalid name',   // Contains space
            '',              // Empty string
            'var$name',      // Contains $
        ];

        foreach ($invalidNames as $invalidName) {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage("Invalid variable name: {$invalidName}");

            $locator->setRoutesVarName($invalidName);
        }
    }

    #[Test]
    #[TestDox('setRoutesVarName accepts valid variable names')]
    public function set_routes_var_name_accepts_valid_variable_names(): void
    {
        $this->createSimpleRoutesFile();
        $locator = new RouteLocator($this->tempFile);

        $validNames = [
            'routes',
            'myRoutes',
            '_routes',
            'routes2',
            'ROUTES',
            'r',
            '_',
            'routes_collection',
            'routesCollection',
        ];

        foreach ($validNames as $validName) {
            $result = $locator->setRoutesVarName($validName);
            $this->assertSame($locator, $result);
        }
    }

    #[Test]
    #[TestDox('getRoutes returns RoutesCache when useCache is true')]
    public function get_routes_returns_routes_cache_when_use_cache_is_true(): void
    {
        $this->createCacheRoutesFile();

        $locator = new RouteLocator($this->tempFile, [], $this->compiler, true);
        $routes = $locator->getRoutes();

        $this->assertInstanceOf(RoutesCache::class, $routes);
    }

    #[Test]
    #[TestDox('getRoutes returns Routes when useCache is false')]
    public function get_routes_returns_routes_when_use_cache_is_false(): void
    {
        $this->createRegularRoutesFile();

        $locator = new RouteLocator($this->tempFile, [], $this->compiler, false);
        $routes = $locator->getRoutes();

        $this->assertInstanceOf(Routes::class, $routes);
    }

    #[Test]
    #[TestDox('getRoutes with cache mode extracts context variables')]
    public function get_routes_with_cache_mode_extracts_context_variables(): void
    {
        $this->createCacheRoutesFileWithContext();

        $context = ['config' => ['debug' => true]];
        $locator = new RouteLocator($this->tempFile, $context, $this->compiler, true);

        $routes = $locator->getRoutes();
        $this->assertInstanceOf(RoutesCache::class, $routes);
    }

    #[Test]
    #[TestDox('getRoutes works with custom variable name')]
    public function get_routes_works_with_custom_variable_name(): void
    {
        $this->createRegularRoutesFileWithCustomVarName();

        $locator = new RouteLocator($this->tempFile, [], $this->compiler, false, 'myCustomRoutes');
        $routes = $locator->getRoutes();

        $this->assertInstanceOf(Routes::class, $routes);
    }

    #[Test]
    #[TestDox('Context variables are available in route files')]
    public function context_variables_are_available_in_route_files(): void
    {
        $this->createRoutesFileUsingContext();

        $context = [
            'controller' => 'TestController',
            'middleware' => ['AuthMiddleware'],
            'prefix' => '/api/v1',
        ];

        $locator = new RouteLocator($this->tempFile, $context, $this->compiler, false);
        $routes = $locator->getRoutes();

        $this->assertInstanceOf(Routes::class, $routes);

        // Verify that routes were created using context variables
        $route = $routes->getRoute('api.test');
        $this->assertNotNull($route);
        $this->assertEquals('TestController', $route->handler);
    }

    #[Test]
    #[TestDox('Route file syntax errors are properly propagated')]
    public function route_file_syntax_errors_are_properly_propagated(): void
    {
        $this->createInvalidSyntaxRoutesFile();

        $locator = new RouteLocator($this->tempFile, [], $this->compiler, false);

        $this->expectException(\ParseError::class);
        $locator->getRoutes();
    }

    #[Test]
    #[TestDox('Route file runtime errors are properly propagated')]
    public function route_file_runtime_errors_are_properly_propagated(): void
    {
        $this->createRuntimeErrorRoutesFile();

        $locator = new RouteLocator($this->tempFile, [], $this->compiler, false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Runtime error in routes file');

        $locator->getRoutes();
    }

    #[Test]
    #[TestDox('Multiple calls to getRoutes work correctly')]
    public function multiple_calls_to_get_routes_work_correctly(): void
    {
        $this->createRegularRoutesFile();

        $locator = new RouteLocator($this->tempFile, [], $this->compiler, false);

        $routes1 = $locator->getRoutes();
        $routes2 = $locator->getRoutes();

        // Should create new instances each time (not cached)
        $this->assertNotSame($routes1, $routes2);
        $this->assertInstanceOf(Routes::class, $routes1);
        $this->assertInstanceOf(Routes::class, $routes2);
    }

    #[Test]
    #[TestDox('Integration test with realistic route configuration')]
    public function integration_test_with_realistic_route_configuration(): void
    {
        $this->createRealisticRoutesFile();

        $context = [
            'authMiddleware' => 'AuthMiddleware',
            'apiPrefix' => '/api/v1',
            'controllers' => [
                'user' => 'App\\Controllers\\UserController',
                'post' => 'App\\Controllers\\PostController',
            ],
        ];

        $locator = new RouteLocator($this->tempFile, $context, $this->compiler, false);
        $routes = $locator->getRoutes();

        $this->assertInstanceOf(Routes::class, $routes);

        // Verify some routes were created
        $userRoute = $routes->getRoute('api.users.show');
        $this->assertNotNull($userRoute);
        $this->assertEquals('App\\Controllers\\UserController', $userRoute->handler);

        $postRoute = $routes->getRoute('api.posts.index');
        $this->assertNotNull($postRoute);
        $this->assertEquals('App\\Controllers\\PostController', $postRoute->handler);
    }

    /**
     * Helper methods to create test route files
     */
    private function createSimpleRoutesFile(?string $filename = null): void
    {
        $filename = $filename ?? $this->tempFile;
        $content = "<?php\nreturn ['static' => [], 'dynamic' => []];";
        file_put_contents($filename, $content);
    }

    private function createCacheRoutesFile(): void
    {
        $content = "<?php\nreturn [\n    'static' => [],\n    'dynamic' => []\n];";
        file_put_contents($this->tempFile, $content);
    }

    private function createCacheRoutesFileWithContext(): void
    {
        $content = "<?php\n// Context variable \$config should be available\nreturn [\n    'static' => [],\n    'dynamic' => []\n];";
        file_put_contents($this->tempFile, $content);
    }

    private function createRegularRoutesFile(): void
    {
        $content = "<?php\n\$routes->addRoute(\\Bermuda\\Router\\RouteRecord::get('home', '/', 'HomeController'));";
        file_put_contents($this->tempFile, $content);
    }

    private function createRegularRoutesFileWithContext(): void
    {
        $content = "<?php\n// \$baseUrl should be available from context\n\$routes->addRoute(\\Bermuda\\Router\\RouteRecord::get('home', '/', 'HomeController'));";
        file_put_contents($this->tempFile, $content);
    }

    private function createRegularRoutesFileWithCustomVarName(): void
    {
        $content = "<?php\n\$myCustomRoutes->addRoute(\\Bermuda\\Router\\RouteRecord::get('test', '/test', 'TestController'));";
        file_put_contents($this->tempFile, $content);
    }

    private function createRoutesFileUsingContext(): void
    {
        $content = "<?php\n" .
            "// Using context variables\n" .
            "\$group = \$routes->group('api', \$prefix);\n" .
            "\$group->get('test', '/test', \$controller);\n" .
            "\$group->setMiddleware(\$middleware);";
        file_put_contents($this->tempFile, $content);
    }

    private function createInvalidSyntaxRoutesFile(): void
    {
        $content = "<?php\n// Invalid PHP syntax\n\$routes->addRoute( invalid syntax here";
        file_put_contents($this->tempFile, $content);
    }

    private function createRuntimeErrorRoutesFile(): void
    {
        $content = "<?php\nthrow new \\RuntimeException('Runtime error in routes file');";
        file_put_contents($this->tempFile, $content);
    }

    private function createRealisticRoutesFile(): void
    {
        $content = "<?php\n" .
            "use Bermuda\\Router\\RouteRecord;\n\n" .
            "// Create API group\n" .
            "\$apiGroup = \$routes->group('api', \$apiPrefix);\n" .
            "\$apiGroup->setMiddleware([\$authMiddleware]);\n\n" .
            "// User routes\n" .
            "\$apiGroup->get('users.index', '/users', \$controllers['user']);\n" .
            "\$apiGroup->get('users.show', '/users/[id]', \$controllers['user']);\n" .
            "\$apiGroup->post('users.store', '/users', \$controllers['user']);\n\n" .
            "// Post routes\n" .
            "\$apiGroup->get('posts.index', '/posts', \$controllers['post']);\n" .
            "\$apiGroup->get('posts.show', '/posts/[id]', \$controllers['post']);";
        file_put_contents($this->tempFile, $content);
    }

    #[Test]
    #[TestDox('Security test - context extraction does not allow code injection')]
    public function security_test_context_extraction_does_not_allow_code_injection(): void
    {
        // This test ensures that context variable names can't be used for code injection
        $this->createSimpleRoutesFile();

        $maliciousContext = [
            'normalVar' => 'safe_value',
            // These should be treated as regular variables, not executed code
            'system("rm -rf /")' => 'malicious',
            'eval("echo evil;")' => 'another_malicious',
        ];

        $locator = new RouteLocator($this->tempFile, $maliciousContext, $this->compiler, true);

        // Should not execute malicious code, just extract variables normally
        $routes = $locator->getRoutes();
        $this->assertInstanceOf(RoutesCache::class, $routes);
    }

    #[Test]
    #[TestDox('Performance test for route loading')]
    public function performance_test_for_route_loading(): void
    {
        $this->createRealisticRoutesFile();

        $context = [
            'authMiddleware' => 'AuthMiddleware',
            'apiPrefix' => '/api/v1',
            'controllers' => [
                'user' => 'UserController',
                'post' => 'PostController',
            ],
        ];

        $locator = new RouteLocator($this->tempFile, $context, $this->compiler, false);

        $iterations = 100;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $routes = $locator->getRoutes();
            $this->assertInstanceOf(RouteMap::class, $routes);
        }

        $totalTime = microtime(true) - $startTime;

        // Should load routes reasonably quickly
        $this->assertLessThan(2.0, $totalTime,
            "Route loading is too slow: {$totalTime}s for {$iterations} iterations");
    }
}