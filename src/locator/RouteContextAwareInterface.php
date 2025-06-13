<?php

namespace Bermuda\Router\Locator;

/**
 * Interface for Route Context Awareness
 *
 * This interface defines the contract for objects that can accept and manage
 * contextual variables for route processing. Context variables are typically
 * used to provide additional data, services, or dependencies to route handlers,
 * middleware, and other routing components.
 *
 * Context variables are commonly used for:
 * - Dependency injection in route closures
 * - Application services and configurations
 * - Request-specific data and metadata
 * - Shared state between routing components
 *
 * Implementing classes should ensure that context variables are properly
 * stored and made available during route processing, typically by extracting
 * them into the appropriate scope where route handlers are executed.
 *
 * @example
 * ```php
 * $locator = new SomeRouteLocator();
 * $locator->setContext([
 *     'app' => $application,
 *     'container' => $diContainer,
 *     'config' => $configuration
 * ]);
 *
 * // Context variables will be available in route files:
 * // $routes->get('/users', function() use ($app) {
 * //     return $app->getUsers();
 * // });
 * ```
 */
interface RouteContextAwareInterface
{
    /**
     * Set context variables for route processing
     *
     * Configures the contextual variables that will be made available during
     * route processing. These variables can be accessed by route handlers,
     * middleware, and other routing components depending on the implementation.
     *
     * Context variables are typically extracted into the local scope where
     * route definitions are loaded, allowing closures to access application
     * services and dependencies through use() statements.
     *
     * The implementation should store these variables and ensure they are
     * properly available when routes are processed or executed.
     *
     * @param array $context Associative array of context variables to make available
     * @return RouteContextAwareInterface Returns this instance for method chaining
     *
     * @example
     * ```php
     * $locator->setContext([
     *     'db' => $databaseConnection,
     *     'cache' => $cacheService,
     *     'logger' => $loggerInstance,
     *     'config' => $appConfiguration
     * ]);
     *
     * // In route files, these variables can be used:
     * $routes->post('/users', function($request) use ($db, $logger) {
     *     $logger->info('Creating new user');
     *     return $db->users()->create($request->getParsedBody());
     * });
     * ```
     */
    public function setContext(array $context): RouteContextAwareInterface;
}