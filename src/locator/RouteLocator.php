<?php

namespace Bermuda\Router\Locator;

use Bermuda\Router\Compiler;
use Bermuda\Router\CompilerInterface;
use Bermuda\Router\ConfigProvider;
use Bermuda\Router\Exception\RouterException;
use Bermuda\Router\RouteMap;
use Bermuda\Router\Routes;
use Bermuda\Router\RoutesCache;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

use function Bermuda\Config\conf;

/**
 * Route Locator for File-Based Route Configuration
 *
 * This class is responsible for locating and loading route definitions from configuration files,
 * with support for caching, context variables, and custom route compilation. It provides a
 * standardized way of retrieving routes from file-based configurations.
 *
 * Key Features:
 * - File-based route configuration loading
 * - Context variable injection for closures and dependencies
 * - Route caching for production performance
 * - Flexible compiler configuration
 * - Container integration for dependency injection
 *
 * The locator supports two modes of operation:
 * 1. Cached mode: Loads pre-compiled route data for performance
 * 2. Fresh mode: Includes route files and builds routes dynamically
 *
 * Context variables are automatically extracted into the route file scope,
 * enabling closures to access application dependencies through use() statements.
 */
final class RouteLocator implements RouteLocatorInterface, RouteContextAwareInterface
{
    /**
     * Path to the route configuration file
     *
     * Stores the absolute path to the PHP file containing route definitions.
     * The file must exist and be readable, or an exception will be thrown.
     */
    private(set) string $filename;

    /**
     * Initialize the route locator with configuration options
     *
     * Creates a new route locator instance with the specified configuration.
     * The filename parameter is required and must point to an existing file.
     * All other parameters have sensible defaults for common use cases.
     *
     * @param string $filename Path to the route configuration file (must exist)
     * @param array $context Context variables to be extracted in the route file scope
     * @param CompilerInterface $compiler Route compiler for processing route definitions
     * @param bool $useCache Whether to enable route caching for performance optimization
     * @param string $varName Variable name to use for the routes object in included files
     *
     * @throws RouterException If the specified file does not exist
     *
     * @example
     * ```php
     * $locator = new RouteLocator(
     *     filename: 'config/routes.php',
     *     context: ['app' => $app, 'container' => $container],
     *     useCache: false
     * );
     * ```
     */
    public function __construct(
        string $filename,
        public array $context = [],
        public CompilerInterface $compiler = new Compiler,
        public bool $useCache = false,
        private(set) string $varName = 'routes'
    ){
        $this->useCache = $useCache;
        $this->setFilename($filename);
    }

    /**
     * Set the path to the route configuration file
     *
     * Updates the configuration file path after validating that the file exists.
     * This method allows changing the route file location after instantiation
     * while ensuring the new file is accessible.
     *
     * @param string $filename Path to the route configuration file
     * @return self Returns this instance for method chaining
     * @throws RouterException If the specified file does not exist or is not readable
     *
     * @example
     * ```php
     * $locator->setFilename('/app/config/production-routes.php');
     * ```
     */
    public function setFilename(string $filename): self
    {
        if (!file_exists($filename)) {
            throw new RouterException("Route file: $filename does not exist");
        }

        $this->filename = $filename;
        return $this;
    }

    /**
     * Set context variables to be made available in the route file
     *
     * These variables will be extracted into the local scope when the route file is included,
     * allowing route definitions to access additional data or services. This is particularly
     * useful for closures that need access to application dependencies through use() statements.
     *
     * Context variables are automatically available in cached route files and will be
     * included in generated docblocks for better IDE support.
     *
     * @param array $context Associative array of variables to extract into file scope
     * @return self Returns this instance for method chaining
     *
     * @example
     * ```php
     * $locator->setContext([
     *     'app' => $application,
     *     'container' => $diContainer,
     *     'config' => $appConfig
     * ]);
     *
     * // In routes.php file:
     * $routes->addRoute(RouteRecord::get('home', '/',
     *     function() use ($app) {
     *         return $app->respond('Hello World');
     *     }
     * ));
     * ```
     */
    public function setContext(array $context = []): RouteContextAwareInterface
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Set the route compiler instance
     *
     * Configures the compiler used for processing route patterns and generating
     * regular expressions. Different compilers can provide different features
     * or performance characteristics.
     *
     * @param CompilerInterface $compiler The compiler to use for processing routes
     * @return self Returns this instance for method chaining
     *
     * @example
     * ```php
     * $customCompiler = new Compiler(['id' => '\d+', 'slug' => '[a-z0-9-]+']);
     * $locator->setCompiler($customCompiler);
     * ```
     */
    public function setCompiler(CompilerInterface $compiler): self
    {
        $this->compiler = $compiler;
        return $this;
    }

    /**
     * Enable or disable route caching
     *
     * Controls whether routes are loaded from cache (for performance) or
     * loaded fresh from the configuration file (for development). Caching
     * should generally be disabled in development and enabled in production.
     *
     * When caching is enabled:
     * - The route file is expected to return cached route data
     * - Context variables are still extracted for closure dependencies
     * - Performance is significantly improved
     *
     * When caching is disabled:
     * - The route file is included and executed
     * - A Routes instance is created and made available as $routes
     * - Suitable for development where routes change frequently
     *
     * @param bool $useCache Whether to use caching
     * @return self Returns this instance for method chaining
     *
     * @example
     * ```php
     * // Development mode
     * $locator->useCache(false);
     *
     * // Production mode
     * $locator->useCache(true);
     * ```
     */
    public function useCache(bool $useCache): self
    {
        $this->useCache = $useCache;
        return $this;
    }

    /**
     * Set the variable name for the routes object in included files
     *
     * This variable will be available in the route configuration file and should
     * be used to register routes. The variable name must follow PHP variable
     * naming conventions (start with letter or underscore, followed by
     * letters, numbers, or underscores).
     *
     * @param string $varName Valid PHP variable name (without $ prefix)
     * @return self Returns this instance for method chaining
     * @throws InvalidArgumentException If the variable name is not valid
     *
     * @example
     * ```php
     * $locator->setRoutesVarName('router');
     *
     * // In routes.php file:
     * $router->addRoute(RouteRecord::get('home', '/', 'HomeController'));
     * ```
     */
    public function setRoutesVarName(string $varName): self
    {
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $varName) !== 1) {
            throw new InvalidArgumentException(
                "Invalid variable name: $varName. Variable name must start with a letter or underscore, followed by letters, numbers, or underscores."
            );
        }

        $this->varName = $varName;
        return $this;
    }

    /**
     * Retrieve the routing map containing all registered routes
     *
     * Loads routes from the configured file using either cached or fresh loading
     * based on the useCache setting. Context variables are extracted into the
     * file scope to provide access to application dependencies.
     *
     * Caching Mode (useCache = true):
     * - Extracts context variables for closure dependencies
     * - Includes the file expecting it to return cached route data
     * - Creates RoutesCache instance with the loaded data
     *
     * Fresh Mode (useCache = false):
     * - Creates a new Routes instance and adds it to context
     * - Extracts all context variables including the routes instance
     * - Includes the route file which registers routes on the instance
     * - Returns the populated Routes instance
     *
     * @return RouteMap The map of routes loaded from the configuration file
     * @throws RouterException If route file loading fails
     *
     * @example
     * ```php
     * // Load routes and create router
     * $routes = $locator->getRoutes();
     * $router = Router::fromDnf($routes);
     *
     * // Match incoming request
     * $route = $router->match($uri, $method);
     * ```
     */
    public function getRoutes(): RouteMap
    {
        if ($this->useCache) {
            // Extract context variables for closure dependencies in cached routes
            if ($this->context !== []) {
                extract($this->context);
            }

            // Load cached route data and create RoutesCache instance
            return new RoutesCache(include $this->filename, $this->compiler);
        }

        // Create fresh Routes instance and add to context
        $this->context[$this->varName] = new Routes($this->compiler);

        // Extract all context variables including the routes instance
        extract($this->context);

        // Include route file which will register routes on the instance
        include $this->filename;

        // Return the populated Routes instance
        return $this->context[$this->varName];
    }

    /**
     * Create RouteLocator instance from dependency injection container
     *
     * Factory method for creating a RouteLocator when using dependency injection
     * containers. Automatically resolves dependencies and configuration from
     * the container to create a properly configured instance.
     *
     * The method resolves configuration through a 'config' service that provides
     * access to application configuration values using the ConfigProvider constants.
     *
     * Configuration Keys:
     * - ConfigProvider::CONFIG_KEY_ROUTES_FILE - Path to routes configuration file
     * - ConfigProvider::CONFIG_KEY_USE_CACHE - Boolean whether to enable route caching
     * - ConfigProvider::CONFIG_KEY_CONTEXT - Context variables for route files
     *
     * @param ContainerInterface $container DI container with route configuration
     * @return self Configured RouteLocator instance
     * @throws RouterException If required configuration is missing or invalid
     *
     * @example
     * ```php
     * // In your container configuration:
     * $config->set(ConfigProvider::CONFIG_KEY_ROUTES_FILE, __DIR__ . '/config/routes.php');
     * $config->set(ConfigProvider::CONFIG_KEY_USE_CACHE, $_ENV['APP_ENV'] === 'production');
     * $config->set(ConfigProvider::CONFIG_KEY_CONTEXT, ['app' => $app, 'config' => $config]);
     *
     * // Create locator from container
     * $locator = RouteLocator::createFromContainer($container);
     * $routes = $locator->getRoutes();
     * ```
     */
    public static function createFromContainer(ContainerInterface $container): self
    {
        $config = conf($container);
        $routesFile = $config->get(ConfigProvider::CONFIG_KEY_ROUTES_FILE, getcwd() . '/config/routes.php');

        $compiler = $container->has(CompilerInterface::class)
            ? $container->get(CompilerInterface::class)
            : new Compiler();

        $useCache = $config->get(ConfigProvider::CONFIG_KEY_USE_CACHE, false);
        $context = $config->get(ConfigProvider::CONFIG_KEY_CONTEXT, []);

        return new self(
            filename: $routesFile,
            context: $context,
            compiler: $compiler,
            useCache: $useCache
        );
    }
}