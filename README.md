 # Installation
 ```bash
 composer require bermudaphp/router
 ````
 
 ## Usage

 ```php
 $router = new Router();
 
 $router->add(['name' => 'home', 'path' => '/home/{name}', 'handler' => function(string $name){echo sprintf('Hello, %s!', $name)}, ['methods' => ['GET|POST']]]);
 
 try
 {
    $route = $router->match($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
 }
 
 catch(Exception\RouteNotFoundException|Exception\MethodNotAllowedException)
 {
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
  
 try
 {
    $response = $pipeline->handle($request);
 }
 
 catch(Exception\RouteNotFoundException|Exception\MethodNotAllowedException)
 {
    // handle exception logics
 }

 send($response)
 ```
 
 ## RouteMap HTTP Methods
 
 ```php
 
 $mutators = ['tokens' => ['id' => 'd+'], 'middleware' => [MyMiddleware::class]];
 
 $routes->get($name, $path, $handler, $mutators);
 $routes->post($name, $path, $handler, $mutators);
 $routes->patch($name, $path, $handler, $mutators);
 $routes->put($name, $path, $handler, $mutators);
 $routes->delete($name, $path, $handler, $mutators);
 $routes->options($name, $path, $handler, $mutators);
 $routes->any($name, $path, $handler, array_merge($mutators, ['methods' => ['GET', 'POST']]));
 ```
  
 ## Routes Group
 
 ```php
 $routes->group('/admin', static function(RouteMap $routes)
 {
    $routes->get('index', '/', $handler);
    $routes->get('users', '/users', $handler);
    $routes->post('add.user', '/add/user', $handler);
 });
 ```
