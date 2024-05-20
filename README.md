 # Installation
 ```bash
 composer require bermudaphp/router
 ````
 ## Relevance of documentation
 This documentation is relevant for all versions starting from 3.1
 ## Usage

 ```php
 $routes = new Routes;
 $router = new Router($routes, $routes, $routes);

 $routes->get('home', '/hello/{name}', static function(string $name): void {
     echo sprintf('Hello, %s!', $name)
 });
 
 try {
    $route = $router->match($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
 } catch(Exception\RouteNotFoundException|Exception\MethodNotAllowedException) {
    // handle exception logics
 }
 
 call_user_func($route->getHandler(), $route->getAttributes()['name']);
 ```
 ## Route path generation
 ```php
 echo $router->generate('home', ['name' => 'Jane']); // Output /hello/Jane
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
 ## Get current route data
 
 ```php
 class Handler implements RequestHandlerInterface
 {
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeData = $request->getAttribute('Bermuda\Router\Middleware\RouteMiddleware')->toArray();
        
        dd($routeData) 
    }
 };
 
 ```
 ## RouteMap HTTP Methods
 
 ```php
 $routes->get(string $name, string|Path $path, mixed $handler, ?array $middleware = null);
 $routes->post(string $name, string|Path $path, mixed $handler, ?array $middleware = null);
 $routes->patch(string $name, string|Path $path, mixed $handler, ?array $middleware = null);
 $routes->put(string $name, string|Path $path, mixed $handler, ?array $middleware = null);
 $routes->delete(string $name, string|Path $path, mixed $handler, ?array $middleware = null);
 $routes->options(string $name, string|Path $path, mixed $handler, ?array $middleware = null);
 $routes->any(string $name, string|Path $path, mixed $handler, string|array $methods = null, ?array $middleware = null);
 ```
 
 ## Set attribute placeholder pattern
 
 ```php
 $routes->get('users.get, path('api/v1/client/:name', ['name' => '[a-zA-Z]']), static function(ServerRequestInterface $request): ResponseInterface {
     return get_client_by_name($request->getAttribute('name'));
 });
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
 
 ## Predefined placeholders
 
 ````
 id: \d+
 action: (create|read|update|delete)
 any: .*
 ````
 
 Other placeholders passed to path as a string without being explicitly defined via `path(tokes: $tokens)` will match the pattern `.*`
  
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
 
## Middleware
 
```php
$routes->get($name, $path, $handler, MyMiddleware::class);
or
$routes->get($name, $path, $handler, [FirstMiddleware::class, SecondMiddleware::class]);
```
See: [https://github.com/bermudaphp/psr15factory](https://github.com/bermudaphp/psr15factory)
## Cache
 
Once all routes are registered in the route map and they will no longer be changed. Call the $routes->cache method to cache the route map in a php file. Then use the `Routes::createFromCache('/path/to/cached/routes/filename.php')` method to create a map instance with preloaded routes.

```php
 
 $routes->cache('path/to/cached/routes/file.php');
 $routes = Routes::createFromCache('path/to/cached/routes/file.php')
 
 $router = new Router($routes, $routes, $routes);
 ```
# Cache context
If you are using a parent-context-bound closure (the use construct) as a route handler, then you must pass an array of bound variables to the `Routes::createFromCache` method. See example below
```php
 $app = new App;
 $repository = new UserRepository;
 $routes->get('user.get', '/user/{id}', static function(int $id) use ($app, $repository): ResponseInterface {
    return $app->respond(200, $repository->findById($id));
 });

 $routes->cache('path/to/cached/routes/file.php');
 $routes = Routes::createFromCache('path/to/cached/routes/file.php', compact('app', 'repository'));
 ```
 
 # Cache limitations
 Currently, the caching implementation does not allow caching routes using object instances and callback functions based on object instances.

 # Benchmark
 ```php
 final class Tester
{
    public const iterationCount = 'it_count';
    public const memoryUsage = 'memory_usage';
    public const memoryPeakUsage = 'memory_peak_usage';
    public const execTime = 'exec_time';

    /**
     * @param Benchmark $benchmark
     * @param int $iterationCount
     * @return array
     */
    public function test(Benchmark $benchmark, int $iterationCount = 10000): array
    {
        set_time_limit(1000);
        $result = [
            self::iterationCount => $iterationCount,
        ];

        $start = microtime(true);
        $memory = 0;

        while ($iterationCount--) {
            $benchmark->run();
            $memory += memory_get_usage(true);
        }

        $humanize = static function($size) {
            $unit=['b','kb','mb','gb','tb','pb'];
            return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
        };

        $result[self::execTime] = microtime(true) - $start;
        $result[self::memoryUsage] = $humanize(round($memory/$result[self::iterationCount]));
        $result[self::memoryPeakUsage] = $humanize(round(memory_get_peak_usage(true)));

        return $result;
    }
}

class RouterBenchmark implements Benchmark
{
    public $router = null;
    public function run(): void
    {
        if (!$this->router) {
            $this->router = Router::withDefaults();

            $routes = $this->router->getRoutes();
            $it = 1000;
            while($it--) {
                $routes->any($it+1,
                    new Path('/path/{version}/api/{name}/21', ['version' => '\d', 'name' => 'product']),
                    static fn() => ''
                );
            }

            $routes->get('f22', new Path('/path/{version}/api/{name}/22', ['version' => '\d+', 'name' => 'product']), static fn() => '');
            $this->router = $this->router->withRoutes($routes);
        }

        $this->router->match('GET', '/path/25/api/product/22');
    }
}

dd((new Tester())->test(new RouterBenchmark()));

^ array:4 [▼
  "it_count" => 10000
  "exec_time" => 62.266676139832
  "memory_usage" => "6 mb"
  "memory_peak_usage" => "6 mb"
]

class ChachedRouterBenchmark implements Benchmark
{
    public $router = null;
    public function run(): void
    {
        if (!$this->router) {
            $routes = Routes::createFromCache('chached_routes.php');
            $this->router = new Router($routes, $routes, $routes);
        }

        $this->router->match('GET', '/path/25/api/product/22');
    }
}

dd((new Tester())->test(new ChachedRouterBenchmark()));

^ array:4 [▼
  "it_count" => 10000
  "exec_time" => 3.1582560539246
  "memory_usage" => "10 mb"
  "memory_peak_usage" => "10 mb"
]
 ````

