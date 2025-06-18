# Bermuda Router

[🇷🇺 Русская версия](README.RU.md)

Flexible and performant routing library for PHP 8.4+ with route caching support.

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [Creating Routes](#creating-routes)
  - [HTTP Verb Helper Methods](#http-verb-helper-methods)
  - [Using RouteBuilder](#using-routebuilder)
- [Route Parameters](#route-parameters)
  - [Basic Parameters](#basic-parameters)
  - [Predefined Patterns](#predefined-patterns)
  - [Custom Patterns](#custom-patterns)
  - [Default Values](#default-values)
- [Route Groups](#route-groups)
  - [Group Configuration](#group-configuration)
- [URL Generation](#url-generation)
- [PSR-15 Middleware Integration](#psr-15-middleware-integration)
  - [Basic Setup](#basic-setup)
  - [Using RouteNotFoundHandler](#using-routenotfoundhandler)
  - [Configuring 404 Handler](#configuring-404-handler)
  - [Integration in Middleware Pipeline](#integration-in-middleware-pipeline)
- [Accessing Route Data in Controllers](#accessing-route-data-in-controllers)
- [Route Locators](#route-locators)
  - [Locator Setup](#locator-setup)
  - [Routes File](#routes-file)
- [PHP Attribute-based Route Location](#php-attribute-based-route-location)
  - [Installation](#installation-1)
  - [Route Attribute](#route-attribute)
  - [AttributeRouteLocator Setup](#attributeroutelocator-setup)
  - [ClassFinder Integration](#classfinder-integration)
- [Accessing Route Handler](#accessing-route-handler)
- [Route Caching](#route-caching)
  - [Creating Cache](#creating-cache)
  - [Using Cache](#using-cache)
  - [Cache with Context for Closures](#cache-with-context-for-closures)
  - [Caching Limitations](#caching-limitations)
- [Error Handling](#error-handling)
  - [Exception Types](#exception-types)

## Installation

```bash
composer require bermudaphp/router
```

**Requirements:** PHP 8.4+

## Quick Start

```php
use Bermuda\Router\{Routes, Router, RouteRecord};

// Create routes collection
$routes = new Routes();
$router = Router::fromDnf($routes);

// Add route
$routes->addRoute(
    RouteRecord::get('hello', '/hello/[name]', function(string $name): string {
        return "Hello, $name!";
    })
);

// Match request
$route = $router->match('/hello/John', 'GET');
if ($route) {
    $name = $route->parameters->get('name');
    echo call_user_func($route->handler, $name);
}
```

## Creating Routes

### HTTP Verb Helper Methods

| Method    | HTTP Methods | Description                    | Usage Example                                    |
|-----------|-------------|--------------------------------|--------------------------------------------------|
| `get()`   | GET         | Retrieve data                  | `RouteRecord::get('users.index', '/users', 'UsersController')`        |
| `post()`  | POST        | Create new resources           | `RouteRecord::post('users.store', '/users', 'UsersController::store')` |
| `put()`   | PUT         | Full resource update           | `RouteRecord::put('users.update', '/users/[id]', 'UsersController::update')` |
| `patch()` | PATCH       | Partial resource update        | `RouteRecord::patch('users.patch', '/users/[id]', 'UsersController::patch')` |
| `delete()`| DELETE      | Delete resource                | `RouteRecord::delete('users.destroy', '/users/[id]', 'UsersController::destroy')` |
| `head()`  | HEAD        | Retrieve headers               | `RouteRecord::head('users.check', '/users/[id]', 'UsersController::head')` |
| `options()`| OPTIONS    | Get available methods          | `RouteRecord::options('users.options', '/users', 'UsersController::options')` |
| `any()`   | Custom      | Multiple HTTP methods          | `RouteRecord::any('users.resource', '/users/[id]', 'UsersController', ['GET', 'PUT', 'DELETE'])` |

```php
// GET route for user listing
$routes->addRoute(RouteRecord::get('users.index', '/users', UsersController::class));

// POST route for creating new user
$routes->addRoute(RouteRecord::post('users.store', '/users', 'UsersController::store'));

// PUT route for full user update
$routes->addRoute(RouteRecord::put('users.update', '/users/[id]', 'UsersController::update'));

// PATCH route for partial user update
$routes->addRoute(RouteRecord::patch('users.patch', '/users/[id]', 'UsersController::patch'));

// DELETE route for user deletion
$routes->addRoute(RouteRecord::delete('users.destroy', '/users/[id]', 'UsersController::destroy'));

// Multiple methods for single route
$routes->addRoute(RouteRecord::any('users.resource', '/users/[id]', UsersController::class, 
    ['GET', 'PUT', 'PATCH', 'DELETE']
));

// All HTTP methods (catch-all route)
$routes->addRoute(RouteRecord::any('api.catchall', '/api/[path:.*]', ApiController::class));

// Closure as handler
$routes->addRoute(RouteRecord::get('hello', '/hello/[name]', function(string $name) {
    return "Hello, $name!";
}));
```

### Using RouteBuilder

```php
use Bermuda\Router\RouteBuilder;

$route = RouteBuilder::create('users.show', '/users/[id]')
    ->handler(UsersController::class)
    ->get()
    ->middleware([AuthMiddleware::class, ValidationMiddleware::class])
    ->tokens(['id' => '\d+'])
    ->defaults(['format' => 'json'])
    ->build();

$routes->addRoute($route);
```

## Route Parameters

### Basic Parameters

```php
// Required parameter
$routes->addRoute(RouteRecord::get('user.show', '/users/[id]', 'showUser'));

// Optional parameter
$routes->addRoute(RouteRecord::get('posts.index', '/posts/[?page]', 'listPosts'));

// Multiple parameters
$routes->addRoute(RouteRecord::get('post.show', '/blog/[year]/[month]/[slug]', 'showPost'));
```

### Predefined Patterns

| Name      | Pattern                                                                   | Description                        | Examples                    |
|-----------|---------------------------------------------------------------------------|------------------------------------|-----------------------------|
| `id`      | `\d+`                                                                     | Numeric ID                         | 1, 123, 999                |
| `slug`    | `[a-z0-9-]+`                                                              | URL-compatible string              | hello-world, my-post       |
| `uuid`    | `[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}`          | UUID v4 format                     | 550e8400-e29b-41d4-a716-446655440000 |
| `any`     | `.+`                                                                      | Any characters including slashes   | any/path/here              |
| `alpha`   | `[a-zA-Z]+`                                                               | Letters only                       | Hello, ABC                 |
| `alnum`   | `[a-zA-Z0-9]+`                                                            | Letters and digits                 | Hello123, ABC789           |
| `year`    | `[12]\d{3}`                                                               | 4-digit year (1900-2999)           | 2024, 1995                 |
| `month`   | `0[1-9]\|1[0-2]`                                                          | Month (01-12)                      | 01, 12                     |
| `day`     | `0[1-9]\|[12]\d\|3[01]`                                                   | Day of month (01-31)               | 01, 15, 31                 |
| `locale`  | `[a-z]{2}(_[A-Z]{2})?`                                                    | Locale code                        | en, en_US, fr_FR           |
| `version` | `v?\d+(\.\d+)*`                                                           | Version string                     | 1.0, v2.1.3                |
| `date`    | `\d{4}-\d{2}-\d{2}`                                                       | ISO date (YYYY-MM-DD)              | 2024-12-25                 |

### Custom Patterns

#### Inline Patterns

Inline patterns allow defining regex patterns directly in route definition. Syntax: `[parameter_name:regular_expression]`

```php
// Inline pattern - numeric ID only
$routes->addRoute(RouteRecord::get('user.show', '/users/[id:\d+]', 'showUser'));

// Inline pattern - API version
$routes->addRoute(RouteRecord::get('api.version', '/api/[version:v\d+]/users', 'apiUsers'));

// Inline pattern - product SKU (3 letters, dash, 4 digits)
$routes->addRoute(RouteRecord::get('product.show', '/products/[sku:[A-Z]{3}-\d{4}]', 'showProduct'));

// Inline pattern - file format (only specific extensions)
$routes->addRoute(RouteRecord::get('download', '/files/[name]/[format:pdf|doc|txt]', 'downloadFile'));

// Optional inline pattern
$routes->addRoute(RouteRecord::get('posts.category', '/posts/[?category:tech|news|blog]', 'showCategory'));

// Complex inline pattern - date in YYYY-MM-DD format
$routes->addRoute(RouteRecord::get('archive.date', '/archive/[date:\d{4}-\d{2}-\d{2}]', 'showArchive'));
```

#### Setting Patterns via Methods

```php
// Set pattern via method
$route = RouteRecord::get('product.show', '/products/[sku]', 'showProduct')
    ->withToken('sku', '[A-Z]{3}-\d{4}');

// Multiple patterns
$route = RouteRecord::get('complex.route', '/app/[locale]/[category]/[item]', 'handler')
    ->withTokens([
        'locale' => '[a-z]{2}(_[A-Z]{2})?',
        'category' => '[a-z0-9-]+', 
        'item' => '\d+'
    ]);
```

#### Pattern Priority

Patterns are applied in the following priority order (highest to lowest):

1. **Inline patterns** in route: `[id:\d+]`
2. **Method patterns**: `->withToken('id', '\d+')`  
3. **Group patterns**: `$group->setTokens(['id' => '\d+'])`
4. **Predefined patterns**: from table above
5. **Default pattern**: `[^\/]+` (any characters except slash)

### Default Values

```php
$route = RouteRecord::get('posts', '/posts/[?page]', 'listPosts')
    ->withDefaults([
        'page' => '1'
    ]);

// Single value
$route = $route->withDefaultValue('page', '1');
```

## Route Groups

Groups allow organizing related routes with common settings:

```php
// Create group
$apiGroup = $routes->group('api', '/api/v1');

// Add routes to group
$apiGroup->get('users.index', '/users', UsersController::class);
$apiGroup->post('users.store', '/users', 'UsersController::store');
$apiGroup->get('users.show', '/users/[id]', 'UsersController::show');

// Resulting routes:
// api.users.index -> GET /api/v1/users
// api.users.store -> POST /api/v1/users  
// api.users.show -> GET /api/v1/users/[id]
```

### Group Configuration

```php
// Middleware for entire group
$apiGroup->addMiddleware(AuthMiddleware::class)
         ->addMiddleware(RateLimitMiddleware::class);

// Patterns for entire group
$apiGroup->setTokens([
    'id' => '\d+',
    'slug' => '[a-z0-9-]+',
    'locale' => '[a-z]{2}'
]);

// Replace entire middleware stack
$apiGroup->setMiddleware([
    CorsMiddleware::class,
    AuthMiddleware::class,
    LoggingMiddleware::class
]);
```

## URL Generation

```php
// Simple generation
echo $router->generate('users.show', ['id' => 123]);
// Result: /users/123

// With optional parameters
echo $router->generate('posts.index', ['page' => 2]);
// Result: /posts/2

echo $router->generate('posts.index'); // optional parameter omitted
// Result: /posts

// Complex parameters
echo $router->generate('blog.post', [
    'year' => 2024,
    'month' => 3,
    'slug' => 'new-article'
]);
// Result: /blog/2024/3/new-article
```

## PSR-15 Middleware Integration

### Basic Setup

```php
use Bermuda\Router\Middleware\{MatchRouteMiddleware, DispatchRouteMiddleware, RouteNotFoundHandler};
use Bermuda\Pipeline\Pipeline;
use Bermuda\MiddlewareFactory\MiddlewareFactory;

$pipeline = new Pipeline();
$factory = new MiddlewareFactory($container, $responseFactory);

// Middleware for route matching
$pipeline->pipe($factory->makeMiddleware(MatchRouteMiddleware::class));

// Create 404 handler
$notFoundHandler = new RouteNotFoundHandler($responseFactory);

// Middleware for route execution with fallback handler
$pipeline->pipe(new DispatchRouteMiddleware($notFoundHandler));

$response = $pipeline->handle($request);
```

### Using RouteNotFoundHandler

RouteNotFoundHandler handles requests for non-existent routes and can work in two modes:

```php
use Bermuda\Router\Middleware\RouteNotFoundHandler;

// JSON response mode (default)
$notFoundHandler = new RouteNotFoundHandler(
    $responseFactory,
    exceptionMode: false,
    customMessage: 'Requested resource not found'
);

// Example JSON response:
// {
//     "error": "Not Found",
//     "code": 404,
//     "message": "Requested resource not found",
//     "path": "/api/users/999",
//     "method": "GET",
//     "timestamp": "2024-12-25T10:30:00+00:00"
// }

// Exception mode
$notFoundHandler = new RouteNotFoundHandler(
    $responseFactory,
    exceptionMode: true // will throw RouteNotFoundException
);

// Dynamic mode switching via request attributes
$request = $notFoundHandler->withExceptionModeAttribute($request, true);

// Check current mode
$isExceptionMode = $notFoundHandler->getExceptionMode($request);
```

### Integration in Middleware Pipeline

```php
use Bermuda\Router\Middleware\{MatchRouteMiddleware, DispatchRouteMiddleware, RouteNotFoundHandler};

$pipeline = new Pipeline();

// 1. Try to find route
$pipeline->pipe(new MatchRouteMiddleware($middlewareFactory, $router));

// 2. Create 404 handler
$notFoundHandler = new RouteNotFoundHandler(
    $responseFactory, 
    exceptionMode: false,
    customMessage: 'API endpoint not found'
);

// 3. Execute found route or handle 404
$pipeline->pipe(new DispatchRouteMiddleware($notFoundHandler));

// Process request
$response = $pipeline->handle($request);

// With exceptionMode: true - exception handling
$notFoundHandler = new RouteNotFoundHandler($responseFactory, exceptionMode: true);
$pipeline->pipe(new DispatchRouteMiddleware($notFoundHandler));

try {
    $response = $pipeline->handle($request);
} catch (RouteNotFoundException $e) {
    // Custom exception handling (only works with exceptionMode: true)
    $response = new JsonResponse([
        'error' => 'Route not found',
        'path' => $e->path,
        'method' => $e->requestMethod
    ], 404);
}

// With exceptionMode: false (default) - automatic JSON response
$notFoundHandler = new RouteNotFoundHandler($responseFactory, exceptionMode: false);
$pipeline->pipe(new DispatchRouteMiddleware($notFoundHandler));

$response = $pipeline->handle($request);
// If route not found, RouteNotFoundHandler automatically returns JSON:
// HTTP 404 Not Found
// Content-Type: application/json; charset=utf-8
// {
//     "error": "Not Found",
//     "code": 404,
//     "message": "The requested endpoint was not found.",
//     "path": "/api/users/999",
//     "method": "GET", 
//     "timestamp": "2024-12-25T10:30:00+00:00"
// }
```

## Accessing Route Data in Controllers

```php
use Bermuda\Router\Middleware\RouteMiddleware;

class UserController
{
    public function show(ServerRequestInterface $request): ResponseInterface
    {
        // Get route data
        $routeMiddleware = RouteMiddleware::fromRequest($request);
        $route = $routeMiddleware->route;
        
        // Access parameters
        $userId = $request->getAttribute('id');
        // or
        $userId = $route->parameters->get('id');
        
        // Route information
        $routeName = $route->name;
        $routePath = $route->path;
        
        return new JsonResponse(['user_id' => $userId]);
    }
}
```

## Route Locators

For loading routes from configuration files:

### Locator Setup

```php
use Bermuda\Router\Locator\RouteLocator;

$locator = new RouteLocator(
    filename: '/app/config/routes.php',
    context: [
        'app' => $application,
        'container' => $container,
        'config' => $config
    ],
    useCache: $_ENV['APP_ENV'] === 'production'
);

$routes = $locator->getRoutes();
```

### Routes File

```php
// /app/config/routes.php

/** @var Routes $routes */
/** @var Application $app */
/** @var ContainerInterface $container */

// Simple routes
$routes->addRoute(RouteRecord::get('home', '/', HomeController::class));

// Groups
$apiGroup = $routes->group('api', '/api/v1');
$apiGroup->addMiddleware(CorsMiddleware::class);

$apiGroup->get('users.index', '/users', function() use ($app) {
    return $app->getUsers();
});

$apiGroup->post('users.store', '/users', function($request) use ($container) {
    $service = $container->get(UserService::class);
    return $service->create($request->getParsedBody());
});
```

## PHP Attribute-based Route Location

The library supports automatic route discovery through PHP attributes on controller methods. This allows defining routes declaratively, right next to handlers.

### Installation

Attribute support requires an additional package:

```bash
composer require bermudaphp/attribute-locator
```

### Route Attribute

The `#[Route]` attribute allows defining routes directly on controller methods:

```php
use Bermuda\Router\Attribute\Route;

class UserController
{
    #[Route('users.index', '/users', 'GET')]
    public function index(): ResponseInterface
    {
        // Get user list
        return new JsonResponse($this->userService->getAll());
    }

    #[Route('users.show', '/users/[id]', 'GET')]
    public function show(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        return new JsonResponse($this->userService->getById($id));
    }

    #[Route('users.store', '/users', 'POST', middleware: ['auth', 'validation'])]
    public function store(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();
        $user = $this->userService->create($data);
        return new JsonResponse($user, 201);
    }

    #[Route('users.update', '/users/[id]', 'PUT|PATCH', group: 'api')]
    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        $data = $request->getParsedBody();
        $user = $this->userService->update($id, $data);
        return new JsonResponse($user);
    }

    #[Route('users.destroy', '/users/[id]', 'DELETE', priority: 10)]
    public function destroy(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        $this->userService->delete($id);
        return new JsonResponse(null, 204);
    }
}
```

#### Route Attribute Parameters

| Parameter    | Type            | Description                                    | Example                              |
|--------------|----------------|------------------------------------------------|-------------------------------------|
| `name`       | `string`       | Unique route name                              | `'users.show'`                      |
| `path`       | `string`       | URL pattern with parameters                    | `'/users/[id]'`                     |
| `methods`    | `string\|array`| HTTP methods (string or array)                 | `'GET'`, `'PUT\|PATCH'`, `['GET', 'POST']` |
| `middleware` | `array`        | Middleware array for route                     | `['auth', 'validation']`            |
| `group`      | `string`       | Route group name                               | `'api'`                             |
| `priority`   | `int`          | Route priority (higher = earlier)             | `10`                                |
| `defaults`   | `array`        | Default parameter values                       | `['format' => 'json']`              |

### AttributeRouteLocator Setup

AttributeRouteLocator works as a decorator for existing route locators:

```php
use Bermuda\Router\Locator\{RouteLocator, AttributeRouteLocator};

// Base locator (file-based or any other)
$baseLocator = new RouteLocator('/app/config/routes.php');

// Decorate with attribute locator
$attributeLocator = new AttributeRouteLocator($baseLocator);

// Pass context (if needed)
$attributeLocator->setContext([
    'container' => $container,
    'config' => $config
]);

// Get all routes (file + attributes)
$routes = $attributeLocator->getRoutes();
$router = Router::fromDnf($routes);
```

### ClassFinder Integration

For automatic controller discovery with attributes, ClassFinder is used (already included in dependencies):

> 📋 **Detailed documentation**: [bermudaphp/finder](https://github.com/bermudaphp/classFinder) | [Russian Guide](https://github.com/bermudaphp/classFinder/blob/master/README.RU.md)

```php
use Bermuda\ClassFinder\{ClassFinder, ClassNotifier};
use Bermuda\Router\Locator\AttributeRouteLocator;
use Bermuda\Router\Attribute\Route;

// Create locator
$baseLocator = new RouteLocator('/app/config/routes.php');
$attributeLocator = new AttributeRouteLocator($baseLocator);

// Find all classes in controllers directory
$finder = new ClassFinder();
$classes = $finder->find('src/Controllers/');

// Notify locator about found classes (locator filters classes with Route attributes)
$notifier = new ClassNotifier([$attributeLocator]);
$notifier->notify($classes);

// Get all routes
$routes = $attributeLocator->getRoutes();
$router = Router::fromDnf($routes);
```

#### Full Application Integration

```php
use Bermuda\ClassFinder\{ClassFinder, ClassNotifier};
use Bermuda\Router\Locator\{RouteLocator, AttributeRouteLocator};
use Bermuda\Router\Attribute\Route;

// 1. Create base locator
$baseLocator = new RouteLocator(
    filename: '/app/config/routes.php',
    useCache: $_ENV['APP_ENV'] === 'production'
);

// 2. Create attribute locator as decorator
$attributeLocator = new AttributeRouteLocator($baseLocator);
$attributeLocator->setContext(['app' => $app, 'container' => $container]);

// 3. Find classes in various directories with exclusions
$finder = new ClassFinder();

$controllerClasses = $finder->find(
    paths: [
        'src/Controllers/',     // Main controllers
        'src/Api/',             // API controllers
        'app/Http/Controllers/' // Legacy controllers
    ],
    exclude: ['src/Api/products'] // Exclude specific directory
);

// 4. Notify locator about found classes
// ClassFinder finds all classes in specified directories,
// then AttributeRouteLocator scans class methods for Route attributes
// and registers found methods as route handlers
$notifier = new ClassNotifier([$attributeLocator]);
$notifier->notify($controllerClasses);

// 5. Get all routes and create router
$routes = $attributeLocator->getRoutes();
$router = Router::fromDnf($routes);

// 6. Use in middleware pipeline
$pipeline = new Pipeline();
$pipeline->pipe(new MatchRouteMiddleware($middlewareFactory, $router));
$pipeline->pipe(new DispatchRouteMiddleware($notFoundHandler));

$response = $pipeline->handle($request);
```

#### Groups via Attributes

⚠️ **Important**: Route groups must be predefined in code (e.g., in base locator's routes file), otherwise `RouterException` will be thrown.

```php
// First define groups in base locator's routes.php file
/** @var Routes $routes */
$apiGroup = $routes->group('api', '/api');
$adminGroup = $routes->group('admin', '/admin');

// Now you can use these groups in attributes
class ApiController
{
    #[Route('api.users.index', '/users', 'GET', group: 'api')]
    public function getUsers(): ResponseInterface 
    {
        return new JsonResponse($this->userService->getAll());
    }

    #[Route('api.users.store', '/users', 'POST', group: 'api', middleware: ['auth'])]
    public function createUser(ServerRequestInterface $request): ResponseInterface 
    {
        $data = $request->getParsedBody();
        $user = $this->userService->create($data);
        return new JsonResponse($user, 201);
    }
}

// Group configuration can be added after route loading
$routes = $attributeLocator->getRoutes();

// Configure 'api' group after loading routes
$apiGroup = $routes->group('api');
$apiGroup->addMiddleware(CorsMiddleware::class);
$apiGroup->setTokens(['id' => '\d+']);
```

#### Route Priorities

Priorities determine the order of route checking when matching requests. Routes with higher priority are checked first.

**Priority Rules:**
- Default priority = `0`
- Higher number = higher priority
- Routes are sorted by descending priority (100, 50, 10, 0, -10)
- Order not guaranteed for same priority

**When to use priorities:**
- Special routes should be checked before general ones
- Specific patterns — before wide catch-all routes
- API versioning with fallback to older versions

```php
class RouteController  
{
    // Highest priority - special handling
    #[Route('admin.special', '/admin/special/action', 'POST', priority: 100)]
    public function specialAdminAction(): ResponseInterface 
    {
        return new JsonResponse(['action' => 'special']);
    }

    // High priority - specific route
    #[Route('user.profile', '/users/profile', 'GET', priority: 50)]
    public function userProfile(): ResponseInterface 
    {
        return new JsonResponse(['page' => 'profile']);
    }

    // Medium priority - route with parameter
    #[Route('user.show', '/users/[id]', 'GET', priority: 10)]
    public function showUser(): ResponseInterface 
    {
        return new JsonResponse(['type' => 'user']);
    }

    // Normal priority - general route
    #[Route('users.list', '/users', 'GET', priority: 0)]
    public function listUsers(): ResponseInterface 
    {
        return new JsonResponse(['type' => 'list']);
    }

    // Low priority - catch-all route (checked last)
    #[Route('catch.all', '/[path:.*]', 'GET', priority: -10)]
    public function catchAll(): ResponseInterface 
    {
        return new JsonResponse(['type' => 'fallback']);
    }
}
```

**Priority example for API versioning:**

```php
class ApiController
{
    // v2 API - high priority
    #[Route('api.v2.users', '/api/v2/users/[id]', 'GET', priority: 20)]
    public function getUserV2(): ResponseInterface 
    {
        return new JsonResponse(['version' => 'v2', 'features' => ['new_field']]);
    }

    // v1 API - medium priority  
    #[Route('api.v1.users', '/api/v1/users/[id]', 'GET', priority: 10)]
    public function getUserV1(): ResponseInterface 
    {
        return new JsonResponse(['version' => 'v1']);
    }

    // Fallback to v1 for requests without version - low priority
    #[Route('api.users.fallback', '/api/users/[id]', 'GET', priority: 0)]
    public function getUserFallback(): ResponseInterface 
    {
        // Redirect to v1
        return new JsonResponse(['version' => 'v1', 'deprecated' => true]);
    }
}
```

**Regular routes vs attribute routes:**
- **Attribute routes**: priority determined by `priority` parameter
- **Regular routes**: priority determined by addition order (first added = highest priority)

```php
// Regular routes - addition order determines priority
$routes->addRoute(RouteRecord::get('special', '/api/special', 'handler1')); // Checked first
$routes->addRoute(RouteRecord::get('generic', '/api/[path:.*]', 'handler2')); // Checked second

// Attribute routes - use priority parameter
#[Route('high', '/api/high', 'GET', priority: 100)]    // Checked first
#[Route('low', '/api/low', 'GET', priority: 0)]        // Checked second
```

## Accessing Route Handler

RouteRecord provides convenient access to various route components:

```php
// Create route with middleware
$route = RouteRecord::get('users.show', '/users/[id]', UserController::class)
    ->withMiddlewares([AuthMiddleware::class, ValidationMiddleware::class]);

// Access full pipeline (middleware + handler)
$fullPipeline = $route->pipeline; // [AuthMiddleware::class, ValidationMiddleware::class, UserController::class]

// Access middleware only
$middleware = $route->middleware; // [AuthMiddleware::class, ValidationMiddleware::class]

// Access main handler
$handler = $route->handler; // UserController::class
```

## Route Caching

For improved performance in production:

### Creating Cache

```php
use Bermuda\Router\CacheFileProvider;

// Setup routes
$routes = new Routes();
$routes->addRoute(RouteRecord::get('home', '/', 'HomeController'));
$routes->addRoute(RouteRecord::get('users.show', '/users/[id]', 'UsersController::show'));

// Create cache
$cacheProvider = new CacheFileProvider('/path/to/cache');
$routeData = $routes->toArray();

$cacheProvider->writeFile('routes', $routeData);
```

### Using Cache

```php
use Bermuda\Router\RoutesCache;

// Load cached routes
$cacheProvider = new CacheFileProvider('/path/to/cache');
$cachedData = $cacheProvider->readFile('routes');

$routes = new RoutesCache($cachedData);
$router = Router::fromDnf($routes);
```

### Cache with Context for Closures

When routes use closures with external variables (via `use`), these variables must be available when loading cached routes. Context allows passing necessary objects and data to the cached file scope.

```php
// When creating routes with closures
$app = new Application();
$db = new Database();

$routes->addRoute(RouteRecord::get('users.index', '/users', 
    function() use ($app, $db) {
        return $app->respond($db->users()->all());
    }
));

// Save cache with context
$cacheProvider->writeFile('routes', $routes->toArray(), [
    'app' => $app,
    'db' => $db
]);

// Load with context - variables $app and $db will be available in cached file
$cachedData = $cacheProvider->readFile('routes');
$routes = new RoutesCache($cachedData);
```

### Caching Limitations

The caching system has the following limitations:

#### ❌ What cannot be cached

```php
// 1. Objects as handlers
$controller = new UserController();
$routes->addRoute(RouteRecord::get('users', '/users', $controller)); // Not cached

// 2. Closures with objects in use (without context)
$service = new UserService();
$routes->addRoute(RouteRecord::get('users', '/users', 
    function() use ($service) { 
        return $service->getUsers();
    }
)); // Handler will be cached, but error will occur due to missing context // Undefined variable $service

// 3. Anonymous classes
$routes->addRoute(RouteRecord::get('test', '/test', new class {
    public function handle() { return 'test'; }
})); // Anonymous class not cached
```

#### ✅ What can be cached

```php
// 1. Strings (class and method names)
$routes->addRoute(RouteRecord::get('users', '/users', 'UserController'));
$routes->addRoute(RouteRecord::get('posts', '/posts', 'PostController::index'));

// 2. Arrays with class/method names
$routes->addRoute(RouteRecord::get('api', '/api', ['ApiController', 'handle']));

// 3. Scalar values in context
$routes->addRoute(RouteRecord::get('config', '/config',
    function() use ($appName, $version) { // $appName and $version - strings/numbers
        return ['app' => $appName, 'version' => $version];
    }
));
```

#### 💡 Recommendations

1. **Use string handlers** in production for maximum cache compatibility
2. **Pass contextual data** if using `use` in closure handlers

Most preferred handler type - class name ```MyHandler::class``` or class and method combination ```MyHandler::handle```

## Error Handling

### Exception Types

```php
use Bermuda\Router\Exception\{
    RouterException,
    RouteNotFoundException, 
    RouteNotRegisteredException,
    GeneratorException,
    MatchException
};

try {
    $route = $router->match($uri, $method);
    $url = $router->generate('nonexistent.route', ['id' => 123]);
} catch (RouteNotFoundException $e) {
    // 404 - route not found
    echo "Path not found: $e->path [$e->requestMethod]";
} catch (RouteNotRegisteredException $e) {
    // 500 - route not registered 
    echo "Route '$e->routeName' not registered";
} catch (GeneratorException $e) {
    // 400 - URL generation error
    echo "URL generation error: " . $e->getMessage();
} catch (MatchException $e) {
    // Pattern matching error
    echo "Matching error: $e->pattern for $e->path";
} catch (RouterException $e) {
    // General router errors
    echo "Router error: " . $e->getMessage();
}
```
