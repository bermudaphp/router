<?php

namespace Bermuda\Router\Locator;

use Bermuda\Router\Compiler;
use Bermuda\Router\CompilerInterface;
use Bermuda\Router\Exception\RouterException;
use Bermuda\Router\RouteMap;
use Bermuda\Router\Routes;
use Bermuda\Router\RoutesCache;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

/**
 * RouteLocator implementation for loading routes from configuration files.
 *
 * This class is responsible for locating and loading route definitions from a specified file,
 * with support for caching, context variables, and custom route compilation.
 * It implements the RouteLocatorInterface to provide a standardized way of retrieving routes.
 */
final class RouteLocator implements RouteLocatorInterface
{
    /**
     * Path to the route configuration file.
     */
    private(set) string $filename;

    /**
     * Initialize the route locator with configuration options.
     *
     * @param string $filename Path to the route configuration file
     * @param array $context Context variables to be extracted in the route file scope
     * @param CompilerInterface $compiler Route compiler for processing route definitions
     * @param bool $useCache Whether to enable route caching for performance optimization
     * @param string $varName Variable name to use for the routes object in included files
     */
    public function __construct(
        string $filename,
        public array $context = [],
        public CompilerInterface $compiler = new Compiler,
        public bool $useCache = true,
        private(set) string $varName = 'routes'
    ){
        $this->setFilename($filename);
    }

    /**
     * Set the path to the route configuration file.
     *
     * @param string $filename Path to the route configuration file
     * @return self Returns this instance for method chaining
     * @throws RouterException If the specified file does not exist
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
     * Set context variables to be made available in the route file.
     *
     * These variables will be extracted into the local scope when the route file is included,
     * allowing the route definitions to access additional data or services.
     *
     * @param array $context Associative array of variables to extract
     * @return self Returns this instance for method chaining
     */
    public function setContext(array $context = []): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Set the route compiler instance.
     *
     * @param CompilerInterface $compiler The compiler to use for processing routes
     * @return self Returns this instance for method chaining
     */
    public function setCompiler(CompilerInterface $compiler): self
    {
        $this->compiler = $compiler;
        return $this;
    }

    /**
     * Enable or disable route caching.
     *
     * When caching is enabled, routes are loaded using RoutesCache for improved performance.
     * When disabled, routes are loaded fresh each time using the Routes class.
     *
     * @param bool $useCache Whether to use caching
     * @return self Returns this instance for method chaining
     */
    public function useCache(bool $useCache = true): self
    {
        $this->useCache = $useCache;
        return $this;
    }

    /**
     * Set the variable name for the routes object in included files.
     *
     * This variable will be available in the route configuration file and should
     * be used to register routes (e.g., $routes->addRoute(...)).
     *
     * @param string $varName Valid PHP variable name (without $ prefix)
     * @return self Returns this instance for method chaining
     * @throws InvalidArgumentException If the variable name is not valid
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
     * Retrieves the routing map containing all registered routes.
     *
     * Loads routes from the configured file, either using caching for performance
     * or fresh loading for development. Context variables are extracted into the
     * file scope to provide additional data to route definitions.
     *
     * @return RouteMap The map of routes loaded from the configuration file
     */
    public function getRoutes(): RouteMap
    {
        if ($this->useCache) {
            if ($this->context !== []) {
                extract($this->context);
            }

            return new RoutesCache(include $this->filename, $this->compiler);
        }

        $this->context[$this->varName] = new Routes($this->compiler);

        extract($this->context);

        include $this->filename;

        return $this->context[$this->varName];
    }

    public static function createFromContainer(ContainerInterface $container): self
    {

    }
}