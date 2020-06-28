 # Installation
 ```bash
 composer require bermudaphp/router
 ````
 
 ## Usage

 ```php
 $router = new Router();
 
 $routes = $router->getRoutes();
 
 $routes->add(['name' => 'home', 'path' => '/home/{name}', 'handler' => function(string $name){echo sprintf('Hello, %s!', $name)}])->methods(['GET', 'POST']);
 
 try
 {
    $route = $router->match($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
 }
 
 catch(Exception\RouteNotFoundException|Exception\MethodNotAllowedException)
 {
    // handle exception logics
 }
 
 call_user_func($route->getHandler(), $route->getAttributes('name'));
 ```
 ## Usage with PSR-15
 
 ```php
 
 $pipeline = new \Bermuda\Pipeline\Pipeline();
 
 $factory = new \Bermuda\MiddlewareFactory\MiddlewareFactory($containerInterface, $responseFactoryInterface);
 
 $pipeline->pipe($factory->make(Middleware\MatchRouteMiddleware::class));
 $pipeline->pipe($factory->make(Middleware\DispatchRouteMiddleware::class));
 
 $requestHandler = new class implements RequestHandlerInterface
 {
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new TextResponse(sprintf('Hello, %s!', $request->getAttribute('name')))
    }
 };
 
 try
 {
    $response = $pipeline->process($request, $requestHandler);
 }
 
 catch(Exception\RouteNotFoundException|Exception\MethodNotAllowedException)
 {
    // handle exception logics
 }

 send($response)
 ```
 
 ## RouteMap HTTP Methods
 
 ```php
 $routes->get($name, $path, $handler);
 $routes->post($name, $path, $handler);
 $routes->patch($name, $path, $handler);
 $routes->put($name, $path, $handler);
 $routes->delete($name, $path, $handler);
 $routes->options($name, $path, $handler);
 $routes->any($name, $path, $handler);
 ```
 
 ## Route Tokens
 
 ```php
 $routes->get('home', '/home/{action}/{id}', $handler)->tokens(['id' => 'd+', 'action' => '(create|read|update|delete)'])
 ```
  
 ## Routes Group
 
 ```php
 $routes->group('/admin', function(RouteMap $routes)
 {
    $routes->get('index', '/', $handler);
    $routes->get('users', '/users', $handler);
    $routes->post('add.user', '/add/user', $handler);
 });
 ```
