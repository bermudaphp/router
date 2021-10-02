 # Installation
 ```bash
 composer require bermudaphp/router
 ````
 
 ## Usage

 ```php
 $router = Router::withDefaults();
 
 $route = [
     'name' => 'home',
     'path' => '/home/{name}',
     'handler' => static function(string $name){echo sprintf('Hello, %s!', $name)}, 
     'methods' => 'GET|POST'
 ];
 
 $router->add($route);
 
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
  
 $routes->get($name, $path, $handler);
 $routes->post(['name' => $name', 'middleware' => [MyFirstMiddleware::class, MySecondMiddleware::class]], $path, $handler);
 $routes->patch(compact('name', 'handler', 'path'));
 $routes->put($name, $path, $handler);
 $routes->delete($name, $path, $handler);
 $routes->options($name, $path, $handler);
 $routes->any($name, $path, $handler);
 ```
 
  ## Optional attribute
 
 ```php
 $routes->get('users.get, 'api/v1/users/?{id}', $handler);
 ```
  
 ## Routes Group
 
 ```php
 $routes->group('/admin', static function(RouteMap $routes)
 {
    $routes->get('index', '/', $handler);
    $routes->get('users', '/users', $handler);
    $routes->post('add.user', '/add/user', $handler);
 });
 
 or
 
 $routes->group(['prefix' => '/admin', 'tokens' => ['id' => 'd+'], 'middleware' => MyAdminGroupMiddleware::class], static function(RouteMap $routes)
 {
    $routes->get('index', '/', $handler);
    $routes->get('users', '/users', $handler);
    $routes->post('user.add', '/add/user', $handler);
 });
 ```
