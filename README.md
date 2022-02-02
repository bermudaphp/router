 # Installation
 ```bash
 composer require bermudaphp/router
 ````
 
 ## Usage

 ```php
 $router = Router::withDefaults();
 $router->getRoutes()->get('home', '/hello/{name}', static function(string $name): void {
     echo sprintf('Hello, %s!', $name)
 });
 
 try {
    $route = $router->match($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
 } catch(Exception\RouteNotFoundException|Exception\MethodNotAllowedException) {
    // handle exception logics
 }
 
 call_user_func($route->getHandler(), $route->getAttributes()['name']);
 ```
 ## Usage with PSR-15
 
 ```php
 
 $pipeline = new \Bermuda\Pipeline\Pipeline();
 $factory = new \Bermuda\MiddlewareFactory\MiddlewareFactory($containerInterface, $responseFactoryInterface);
 
 class Handler implements RequestHandlerInterface
 {
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new TextResponse(sprintf('Hello, %s!', $request->getAttribute('name')))
    }
 };
 
 $router->get('home', '/{name}', Handler::class);
 
 $pipeline->pipe($factory->make(Middleware\MatchRouteMiddleware::class));
 $pipeline->pipe($factory->make(Middleware\DispatchRouteMiddleware::class));
  
 try {
    $response = $pipeline->handle($request);
 } catch(Exception\RouteNotFoundException|Exception\MethodNotAllowedException) {
    // handle exception logics
 }

 send($response)
 ```
 
 ## RouteMap HTTP Methods
 
 ```php
 $routes->get(string $name, ?string $path = null, $handler = null, ?array $tokens = null, ?array $middleware = null);
 $routes->post(string $name, ?string $path = null, $handler = null, ?array $tokens = null, ?array $middleware = null);
 $routes->patch(string $name, ?string $path = null, $handler = null, ?array $tokens = null, ?array $middleware = null);
 $routes->put(string $name, ?string $path = null, $handler = null, ?array $tokens = null, ?array $middleware = null);
 $routes->delete(string $name, ?string $path = null, $handler = null, ?array $tokens = null, ?array $middleware = null);
 $routes->options(string $name, ?string $path = null, $handler = null, ?array $tokens = null, ?array $middleware = null);
 $routes->any(string $name, ?string $path = null, $handler = null, string|array $methods =null, ?array $tokens = null, ?array $middleware = null);
 ```
 
  ## Optional attribute
 
 ```php
 $routes->get('users.get, 'api/v1/user/?{id}', static function(ServerRequestInterface $request): ResponseInterface {
     if (($id = $request->getAttribute('id')) !== null) {
         return get_user_by_id($id);
     }
     
     return get_all_users();
 });
 ```
  
 ## Routes Group
 
 ```php
 $routes->group('/admin', callback: static function(RouteMap $routes)
 {
    $routes->get('index', '/', $handler);
    $routes->get('users', '/users', $handler);
    $routes->post('add.user', '/add/user', $handler);
 });
 
 or
 
 $routes->group('/admin', $middleware, $tokens, static function(RouteMap $routes)
 {
    $routes->get('index', '/', $handler);
    $routes->get('users', '/users', $handler);
    $routes->post('user.add', '/add/user', $handler);
 });
 ```
