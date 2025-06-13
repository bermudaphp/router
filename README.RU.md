# Bermuda Router

Гибкая и производительная библиотека маршрутизации для PHP 8.4+ с поддержкой кеширования маршрутов.

## Оглавление

- [Установка](#установка)
- [Быстрый старт](#быстрый-старт)
- [Создание маршрутов](#создание-маршрутов)
  - [Методы-помощники для HTTP глаголов](#методы-помощники-для-http-глаголов)
  - [Использование RouteBuilder](#использование-routebuilder)
- [Параметры маршрутов](#параметры-маршрутов)
  - [Базовые параметры](#базовые-параметры)
  - [Предустановленные паттерны](#предустановленные-паттерны)
  - [Пользовательские паттерны](#пользовательские-паттерны)
  - [Значения по умолчанию](#значения-по-умолчанию)
- [Группы маршрутов](#группы-маршрутов)
  - [Настройка групп](#настройка-групп)
- [Генерация URL](#генерация-url)
- [PSR-15 Middleware интеграция](#psr-15-middleware-интеграция)
  - [Базовая настройка](#базовая-настройка)
  - [Использование RouteNotFoundHandler](#использование-routenotfoundhandler)
  - [Настройка обработчика 404](#настройка-обработчика-404)
  - [Интеграция в middleware pipeline](#интеграция-в-middleware-pipeline)
- [Доступ к данным маршрута в контроллерах](#доступ-к-данным-маршрута-в-контроллерах)
- [Локаторы маршрутов](#локаторы-маршрутов)
  - [Настройка локатора](#настройка-локатора)
  - [Файл маршрутов](#файл-маршрутов)
- [Локация маршрутов на основе PHP атрибутов](#локация-маршрутов-на-основе-php-атрибутов)
  - [Установка](#установка-1)
  - [Атрибут Route](#атрибут-route)
  - [Настройка AttributeRouteLocator](#настройка-attributeroutelocator)
  - [Интеграция с ClassFinder](#интеграция-с-classfinder)
- [Доступ к обработчику маршрута](#доступ-к-обработчику-маршрута)
- [Кеширование маршрутов](#кеширование-маршрутов)
  - [Создание кеша](#создание-кеша)
  - [Использование кеша](#использование-кеша)
  - [Кеш с контекстом для замыканий](#кеш-с-контекстом-для-замыканий)
  - [Ограничения кеширования](#ограничения-кеширования)
- [Обработка ошибок](#обработка-ошибок)
  - [Типы исключений](#типы-исключений)

## Установка

```bash
composer require bermudaphp/router
```

**Требования:** PHP 8.4+

## Быстрый старт

```php
use Bermuda\Router\{Routes, Router, RouteRecord};

// Создание коллекции маршрутов
$routes = new Routes();
$router = Router::fromDnf($routes);

// Добавление маршрута
$routes->addRoute(
    RouteRecord::get('hello', '/hello/[name]', function(string $name): string {
        return "Привет, $name!";
    })
);

// Сопоставление запроса
$route = $router->match('/hello/Ivan', 'GET');
if ($route) {
    $name = $route->parameters->get('name');
    echo call_user_func($route->handler, $name);
}
```

## Создание маршрутов

### Методы-помощники для HTTP глаголов

| Метод     | HTTP методы | Описание                        | Пример использования                                    |
|-----------|-------------|--------------------------------|---------------------------------------------------------|
| `get()`   | GET         | Получение данных               | `RouteRecord::get('users.index', '/users', 'UsersController')`        |
| `post()`  | POST        | Создание новых ресурсов        | `RouteRecord::post('users.store', '/users', 'UsersController::store')` |
| `put()`   | PUT         | Полное обновление ресурса      | `RouteRecord::put('users.update', '/users/[id]', 'UsersController::update')` |
| `patch()` | PATCH       | Частичное обновление ресурса   | `RouteRecord::patch('users.patch', '/users/[id]', 'UsersController::patch')` |
| `delete()`| DELETE      | Удаление ресурса               | `RouteRecord::delete('users.destroy', '/users/[id]', 'UsersController::destroy')` |
| `head()`  | HEAD        | Получение заголовков           | `RouteRecord::head('users.check', '/users/[id]', 'UsersController::head')` |
| `options()`| OPTIONS    | Получение доступных методов    | `RouteRecord::options('users.options', '/users', 'UsersController::options')` |
| `any()`   | Настраиваемые | Множественные HTTP методы    | `RouteRecord::any('users.resource', '/users/[id]', 'UsersController', ['GET', 'PUT', 'DELETE'])` |

```php
// GET маршрут для списка пользователей
$routes->addRoute(RouteRecord::get('users.index', '/users', UsersController::class));

// POST маршрут для создания нового пользователя
$routes->addRoute(RouteRecord::post('users.store', '/users', 'UsersController::store'));

// PUT маршрут для полного обновления пользователя
$routes->addRoute(RouteRecord::put('users.update', '/users/[id]', 'UsersController::update'));

// PATCH маршрут для частичного обновления пользователя
$routes->addRoute(RouteRecord::patch('users.patch', '/users/[id]', 'UsersController::patch'));

// DELETE маршрут для удаления пользователя
$routes->addRoute(RouteRecord::delete('users.destroy', '/users/[id]', 'UsersController::destroy'));

// Множественные методы для одного маршрута
$routes->addRoute(RouteRecord::any('users.resource', '/users/[id]', UsersController::class, 
    ['GET', 'PUT', 'PATCH', 'DELETE']
));

// Все HTTP методы (catch-all маршрут)
$routes->addRoute(RouteRecord::any('api.catchall', '/api/[path:.*]', ApiController::class));

// Замыкание как обработчик
$routes->addRoute(RouteRecord::get('hello', '/hello/[name]', function(string $name) {
    return "Привет, $name!";
}));
```

### Использование RouteBuilder

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

## Параметры маршрутов

### Базовые параметры

```php
// Обязательный параметр
$routes->addRoute(RouteRecord::get('user.show', '/users/[id]', 'showUser'));

// Опциональный параметр
$routes->addRoute(RouteRecord::get('posts.index', '/posts/[?page]', 'listPosts'));

// Множественные параметры
$routes->addRoute(RouteRecord::get('post.show', '/blog/[year]/[month]/[slug]', 'showPost'));
```

### Предустановленные паттерны

| Имя       | Паттерн                                                                   | Описание                           | Примеры                    |
|-----------|---------------------------------------------------------------------------|------------------------------------|-----------------------------|
| `id`      | `\d+`                                                                     | Числовой ID                        | 1, 123, 999                |
| `slug`    | `[a-z0-9-]+`                                                              | URL-совместимая строка             | hello-world, my-post       |
| `uuid`    | `[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}`          | UUID v4 формат                     | 550e8400-e29b-41d4-a716-446655440000 |
| `any`     | `.+`                                                                      | Любые символы включая слеши        | any/path/here              |
| `alpha`   | `[a-zA-Z]+`                                                               | Только буквы                       | Hello, ABC                 |
| `alnum`   | `[a-zA-Z0-9]+`                                                            | Буквы и цифры                      | Hello123, ABC789           |
| `year`    | `[12]\d{3}`                                                               | 4-значный год (1900-2999)          | 2024, 1995                 |
| `month`   | `0[1-9]\|1[0-2]`                                                          | Месяц (01-12)                      | 01, 12                     |
| `day`     | `0[1-9]\|[12]\d\|3[01]`                                                   | День месяца (01-31)                | 01, 15, 31                 |
| `locale`  | `[a-z]{2}(_[A-Z]{2})?`                                                    | Код локали                         | en, en_US, fr_FR           |
| `version` | `v?\d+(\.\d+)*`                                                           | Строка версии                      | 1.0, v2.1.3                |
| `date`    | `\d{4}-\d{2}-\d{2}`                                                       | ISO дата (YYYY-MM-DD)              | 2024-12-25                 |

### Пользовательские паттерны

#### Инлайн паттерны

Инлайн паттерны позволяют определить regex-шаблон непосредственно в определении маршрута. Синтаксис: `[имя_параметра:регулярное_выражение]`

```php
// Инлайн паттерн - ID только из цифр
$routes->addRoute(RouteRecord::get('user.show', '/users/[id:\d+]', 'showUser'));

// Инлайн паттерн - версия API
$routes->addRoute(RouteRecord::get('api.version', '/api/[version:v\d+]/users', 'apiUsers'));

// Инлайн паттерн - SKU товара (3 буквы, тире, 4 цифры)
$routes->addRoute(RouteRecord::get('product.show', '/products/[sku:[A-Z]{3}-\d{4}]', 'showProduct'));

// Инлайн паттерн - формат файла (только определенные расширения)
$routes->addRoute(RouteRecord::get('download', '/files/[name]/[format:pdf|doc|txt]', 'downloadFile'));

// Опциональный инлайн паттерн
$routes->addRoute(RouteRecord::get('posts.category', '/posts/[?category:tech|news|blog]', 'showCategory'));

// Сложный инлайн паттерн - дата в формате YYYY-MM-DD
$routes->addRoute(RouteRecord::get('archive.date', '/archive/[date:\d{4}-\d{2}-\d{2}]', 'showArchive'));
```

#### Установка паттернов через методы

```php
// Установка паттерна через метод
$route = RouteRecord::get('product.show', '/products/[sku]', 'showProduct')
    ->withToken('sku', '[A-Z]{3}-\d{4}');

// Множественные паттерны
$route = RouteRecord::get('complex.route', '/app/[locale]/[category]/[item]', 'handler')
    ->withTokens([
        'locale' => '[a-z]{2}(_[A-Z]{2})?',
        'category' => '[a-z0-9-]+', 
        'item' => '\d+'
    ]);
```

#### Приоритет паттернов

Паттерны применяются в следующем порядке приоритета (от высшего к низшему):

1. **Инлайн паттерны** в маршруте: `[id:\d+]`
2. **Паттерны через методы**: `->withToken('id', '\d+')`  
3. **Группы паттернов**: `$group->setTokens(['id' => '\d+'])`
4. **Предустановленные паттерны**: из таблицы выше
5. **Паттерн по умолчанию**: `[^\/]+` (любые символы кроме слеша)

### Значения по умолчанию

```php
$route = RouteRecord::get('posts', '/posts/[?page]', 'listPosts')
    ->withDefaults([
        'page' => '1'
    ]);

// Одиночное значение
$route = $route->withDefaultValue('page', '1');
```

## Группы маршрутов

Группы позволяют организовать связанные маршруты с общими настройками:

```php
// Создание группы
$apiGroup = $routes->group('api', '/api/v1');

// Добавление маршрутов в группу
$apiGroup->get('users.index', '/users', UsersController::class);
$apiGroup->post('users.store', '/users', 'UsersController::store');
$apiGroup->get('users.show', '/users/[id]', 'UsersController::show');

// Результирующие маршруты:
// api.users.index -> GET /api/v1/users
// api.users.store -> POST /api/v1/users  
// api.users.show -> GET /api/v1/users/[id]
```

### Настройка групп

```php
// Middleware для всей группы
$apiGroup->addMiddleware(AuthMiddleware::class)
         ->addMiddleware(RateLimitMiddleware::class);

// Паттерны для всей группы
$apiGroup->setTokens([
    'id' => '\d+',
    'slug' => '[a-z0-9-]+',
    'locale' => '[a-z]{2}'
]);

// Замена всего middleware стека
$apiGroup->setMiddleware([
    CorsMiddleware::class,
    AuthMiddleware::class,
    LoggingMiddleware::class
]);
```

## Генерация URL

```php
// Простая генерация
echo $router->generate('users.show', ['id' => 123]);
// Результат: /users/123

// С опциональными параметрами
echo $router->generate('posts.index', ['page' => 2]);
// Результат: /posts/2

echo $router->generate('posts.index'); // опциональный параметр пропущен
// Результат: /posts

// Сложные параметры
echo $router->generate('blog.post', [
    'year' => 2024,
    'month' => 3,
    'slug' => 'новая-статья'
]);
// Результат: /blog/2024/3/новая-статья
```

## PSR-15 Middleware интеграция

### Базовая настройка

```php
use Bermuda\Router\Middleware\{MatchRouteMiddleware, DispatchRouteMiddleware, RouteNotFoundHandler};
use Bermuda\Pipeline\Pipeline;
use Bermuda\MiddlewareFactory\MiddlewareFactory;

$pipeline = new Pipeline();
$factory = new MiddlewareFactory($container, $responseFactory);

// Middleware для сопоставления маршрутов
$pipeline->pipe($factory->make(MatchRouteMiddleware::class));

// Создание обработчика 404
$notFoundHandler = new RouteNotFoundHandler($responseFactory);

// Middleware для выполнения маршрутов с fallback handler'ом
$pipeline->pipe(new DispatchRouteMiddleware($notFoundHandler));

$response = $pipeline->handle($request);
```

### Использование RouteNotFoundHandler

RouteNotFoundHandler обрабатывает запросы для несуществующих маршрутов и может работать в двух режимах:

```php
use Bermuda\Router\Middleware\RouteNotFoundHandler;

// Режим JSON ответа (по умолчанию)
$notFoundHandler = new RouteNotFoundHandler(
    $responseFactory,
    exceptionMode: false,
    customMessage: 'Запрошенный ресурс не найден'
);

// Пример JSON ответа:
// {
//     "error": "Not Found",
//     "code": 404,
//     "message": "Запрошенный ресурс не найден",
//     "path": "/api/users/999",
//     "method": "GET",
//     "timestamp": "2024-12-25T10:30:00+00:00"
// }

// Режим исключений
$notFoundHandler = new RouteNotFoundHandler(
    $responseFactory,
    exceptionMode: true // будет выбрасывать RouteNotFoundException
);

// Динамическое переключение режимов через атрибуты запроса
$request = $notFoundHandler->withExceptionModeAttribute($request, true);

// Проверка текущего режима
$isExceptionMode = $notFoundHandler->getExceptionMode($request);
```

### Интеграция в middleware pipeline

```php
use Bermuda\Router\Middleware\{MatchRouteMiddleware, DispatchRouteMiddleware, RouteNotFoundHandler};

$pipeline = new Pipeline();

// 1. Попытка найти маршрут
$pipeline->pipe(new MatchRouteMiddleware($middlewareFactory, $router));

// 2. Создание обработчика 404
$notFoundHandler = new RouteNotFoundHandler(
    $responseFactory, 
    exceptionMode: false,
    customMessage: 'API endpoint not found'
);

// 3. Выполнение найденного маршрута или обработка 404
$pipeline->pipe(new DispatchRouteMiddleware($notFoundHandler));

// Обработка запроса
$response = $pipeline->handle($request);

// При exceptionMode: true - обработка исключений
$notFoundHandler = new RouteNotFoundHandler($responseFactory, exceptionMode: true);
$pipeline->pipe(new DispatchRouteMiddleware($notFoundHandler));

try {
    $response = $pipeline->handle($request);
} catch (RouteNotFoundException $e) {
    // Кастомная обработка исключения (сработает только при exceptionMode: true)
    $response = new JsonResponse([
        'error' => 'Route not found',
        'path' => $e->path,
        'method' => $e->requestMethod
    ], 404);
}

// При exceptionMode: false (по умолчанию) - автоматический JSON ответ
$notFoundHandler = new RouteNotFoundHandler($responseFactory, exceptionMode: false);
$pipeline->pipe(new DispatchRouteMiddleware($notFoundHandler));

$response = $pipeline->handle($request);
// Если маршрут не найден, RouteNotFoundHandler автоматически вернет JSON:
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

## Доступ к данным маршрута в контроллерах

```php
use Bermuda\Router\Middleware\RouteMiddleware;

class UserController
{
    public function show(ServerRequestInterface $request): ResponseInterface
    {
        // Получение данных маршрута
        $routeMiddleware = RouteMiddleware::fromRequest($request);
        $route = $routeMiddleware->route;
        
        // Доступ к параметрам
        $userId = $request->getAttribute('id');
        // или
        $userId = $route->parameters->get('id');
        
        // Информация о маршруте
        $routeName = $route->name;
        $routePath = $route->path;
        
        return new JsonResponse(['user_id' => $userId]);
    }
}
```

## Локаторы маршрутов

Для загрузки маршрутов из файлов конфигурации:

### Настройка локатора

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

### Файл маршрутов

```php
// /app/config/routes.php

/** @var Routes $routes */
/** @var Application $app */
/** @var ContainerInterface $container */

// Простые маршруты
$routes->addRoute(RouteRecord::get('home', '/', HomeController::class));

// Группы
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

## Локация маршрутов на основе PHP атрибутов

Библиотека поддерживает автоматическое обнаружение маршрутов через PHP атрибуты на методах контроллеров. Это позволяет определять маршруты декларативно, прямо рядом с обработчиками.

### Установка

Для использования атрибутов требуется дополнительный пакет:

```bash
composer require bermudaphp/attribute-locator
```

### Атрибут Route

Атрибут `#[Route]` позволяет определить маршрут непосредственно на методе контроллера:

```php
use Bermuda\Router\Attribute\Route;

class UserController
{
    #[Route('users.index', '/users', 'GET')]
    public function index(): ResponseInterface
    {
        // Получение списка пользователей
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

#### Параметры атрибута Route

| Параметр     | Тип            | Описание                                       | Пример                              |
|--------------|----------------|------------------------------------------------|-------------------------------------|
| `name`       | `string`       | Уникальное имя маршрута                        | `'users.show'`                      |
| `path`       | `string`       | URL паттерн с параметрами                      | `'/users/[id]'`                     |
| `methods`    | `string\|array`| HTTP методы (строка или массив)                | `'GET'`, `'PUT\|PATCH'`, `['GET', 'POST']` |
| `middleware` | `array`        | Массив middleware для маршрута                 | `['auth', 'validation']`            |
| `group`      | `string`       | Имя группы маршрутов                           | `'api'`                             |
| `priority`   | `int`          | Приоритет маршрута (выше = раньше)             | `10`                                |
| `defaults`   | `array`        | Значения параметров по умолчанию               | `['format' => 'json']`              |

### Настройка AttributeRouteLocator

AttributeRouteLocator работает как декоратор для существующего локатора маршрутов:

```php
use Bermuda\Router\Locator\{RouteLocator, AttributeRouteLocator};

// Базовый локатор (файловый или любой другой)
$baseLocator = new RouteLocator('/app/config/routes.php');

// Декорирование атрибутным локатором
$attributeLocator = new AttributeRouteLocator($baseLocator);

// Передача контекста (если нужен)
$attributeLocator->setContext([
    'container' => $container,
    'config' => $config
]);

// Получение всех маршрутов (файловые + атрибуты)
$routes = $attributeLocator->getRoutes();
$router = Router::fromDnf($routes);
```

### Интеграция с ClassFinder

Для автоматического обнаружения контроллеров с атрибутами используется ClassFinder (уже включен в зависимости):

> 📋 **Подробная документация**: [bermudaphp/finder](https://github.com/bermudaphp/finder) | [Руководство на русском](https://github.com/bermudaphp/finder/blob/main/README_RU.md)

```php
use Bermuda\ClassFinder\{ClassFinder, ClassNotifier};
use Bermuda\Router\Locator\AttributeRouteLocator;
use Bermuda\Router\Attribute\Route;

// Создание локатора
$baseLocator = new RouteLocator('/app/config/routes.php');
$attributeLocator = new AttributeRouteLocator($baseLocator);

// Поиск всех классов в директории контроллеров
$finder = new ClassFinder();
$classes = $finder->find('src/Controllers/');

// Уведомление локатора о найденных классах (локатор сам фильтрует классы с Route атрибутами)
$notifier = new ClassNotifier([$attributeLocator]);
$notifier->notify($classes);

// Получение всех маршрутов
$routes = $attributeLocator->getRoutes();
$router = Router::fromDnf($routes);
```

#### Полная интеграция в приложение

```php
use Bermuda\ClassFinder\{ClassFinder, ClassNotifier};
use Bermuda\Router\Locator\{RouteLocator, AttributeRouteLocator};
use Bermuda\Router\Attribute\Route;

// 1. Создание базового локатора
$baseLocator = new RouteLocator(
    filename: '/app/config/routes.php',
    useCache: $_ENV['APP_ENV'] === 'production'
);

// 2. Создание атрибутного локатора как декоратора
$attributeLocator = new AttributeRouteLocator($baseLocator);
$attributeLocator->setContext(['app' => $app, 'container' => $container]);

// 3. Поиск классов в различных директориях с исключениями
$finder = new ClassFinder();

$controllerClasses = $finder->find(
    paths: [
        'src/Controllers/',     // Основные контроллеры
        'src/Api/',             // API контроллеры
        'app/Http/Controllers/' // Legacy контроллеры
    ],
    exclude: ['src/Api/products'] // Исключить конкретную директорию
);

// 4. Уведомление локатора о найденных классах
// ClassFinder находит все классы в указанных директориях,
// после чего AttributeRouteLocator сканирует методы классов на наличие Route атрибутов
// и регистрирует найденные методы в качестве обработчиков маршрутов
$notifier = new ClassNotifier([$attributeLocator]);
$notifier->notify($controllerClasses);

// 5. Получение всех маршрутов и создание роутера
$routes = $attributeLocator->getRoutes();
$router = Router::fromDnf($routes);

// 6. Использование в middleware pipeline
$pipeline = new Pipeline();
$pipeline->pipe(new MatchRouteMiddleware($middlewareFactory, $router));
$pipeline->pipe(new DispatchRouteMiddleware($notFoundHandler));

$response = $pipeline->handle($request);
```

#### Группы через атрибуты

⚠️ **Важно**: Группы маршрутов должны быть предварительно определены в коде (например, в файле routes базового локатора), иначе будет выброшено исключение `RouterException`.

```php
// Сначала определите группы в файле routes.php базового локатора
/** @var Routes $routes */
$apiGroup = $routes->group('api', '/api');
$adminGroup = $routes->group('admin', '/admin');

// Теперь можно использовать эти группы в атрибутах
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

// В конфигурации группы можно добавить общие настройки
$routes = $attributeLocator->getRoutes();

// Настройка группы 'api' после загрузки маршрутов
$apiGroup = $routes->group('api');
$apiGroup->addMiddleware(CorsMiddleware::class);
$apiGroup->setTokens(['id' => '\d+']);
```

#### Приоритеты маршрутов

Приоритеты определяют порядок проверки маршрутов при сопоставлении запросов. Маршруты с более высоким приоритетом проверяются первыми.

**Правила приоритетов:**
- По умолчанию приоритет = `0`
- Чем выше число, тем выше приоритет
- Маршруты сортируются по убыванию приоритета (100, 50, 10, 0, -10)
- При одинаковом приоритете порядок не гарантирован

**Когда использовать приоритеты:**
- Специальные маршруты должны проверяться раньше общих
- Конкретные паттерны — раньше широких catch-all маршрутов
- API версионирование с fallback на старые версии

```php
class RouteController  
{
    // Самый высокий приоритет - специальная обработка
    #[Route('admin.special', '/admin/special/action', 'POST', priority: 100)]
    public function specialAdminAction(): ResponseInterface 
    {
        return new JsonResponse(['action' => 'special']);
    }

    // Высокий приоритет - конкретный маршрут
    #[Route('user.profile', '/users/profile', 'GET', priority: 50)]
    public function userProfile(): ResponseInterface 
    {
        return new JsonResponse(['page' => 'profile']);
    }

    // Средний приоритет - маршрут с параметром
    #[Route('user.show', '/users/[id]', 'GET', priority: 10)]
    public function showUser(): ResponseInterface 
    {
        return new JsonResponse(['type' => 'user']);
    }

    // Обычный приоритет - общий маршрут
    #[Route('users.list', '/users', 'GET', priority: 0)]
    public function listUsers(): ResponseInterface 
    {
        return new JsonResponse(['type' => 'list']);
    }

    // Низкий приоритет - catch-all маршрут (проверяется последним)
    #[Route('catch.all', '/[path:.*]', 'GET', priority: -10)]
    public function catchAll(): ResponseInterface 
    {
        return new JsonResponse(['type' => 'fallback']);
    }
}
```

**Пример приоритетов для API версионирования:**

```php
class ApiController
{
    // v2 API - высокий приоритет
    #[Route('api.v2.users', '/api/v2/users/[id]', 'GET', priority: 20)]
    public function getUserV2(): ResponseInterface 
    {
        return new JsonResponse(['version' => 'v2', 'features' => ['new_field']]);
    }

    // v1 API - средний приоритет  
    #[Route('api.v1.users', '/api/v1/users/[id]', 'GET', priority: 10)]
    public function getUserV1(): ResponseInterface 
    {
        return new JsonResponse(['version' => 'v1']);
    }

    // Fallback на v1 для запросов без версии - низкий приоритет
    #[Route('api.users.fallback', '/api/users/[id]', 'GET', priority: 0)]
    public function getUserFallback(): ResponseInterface 
    {
        // Перенаправляем на v1
        return new JsonResponse(['version' => 'v1', 'deprecated' => true]);
    }
}
```

**Обычные маршруты vs атрибутные:**
- **Атрибутные маршруты**: приоритет определяется параметром `priority`
- **Обычные маршруты**: приоритет определяется порядком добавления (первый добавленный = высший приоритет)

```php
// Обычные маршруты - порядок добавления определяет приоритет
$routes->addRoute(RouteRecord::get('special', '/api/special', 'handler1')); // Проверяется первым
$routes->addRoute(RouteRecord::get('generic', '/api/[path:.*]', 'handler2')); // Проверяется вторым

// Атрибутные маршруты - используют параметр priority
#[Route('high', '/api/high', 'GET', priority: 100)]    // Проверяется первым
#[Route('low', '/api/low', 'GET', priority: 0)]        // Проверяется вторым
```
```

## Доступ к обработчику маршрута

RouteRecord предоставляет удобный доступ к различным компонентам маршрута благодаря использованию возможностей PHP 8.4:

```php
// Создание маршрута с middleware
$route = RouteRecord::get('users.show', '/users/[id]', UserController::class)
    ->withMiddlewares([AuthMiddleware::class, ValidationMiddleware::class]);

// Доступ к полному pipeline (middleware + handler)
$fullPipeline = $route->pipeline; // [AuthMiddleware::class, ValidationMiddleware::class, UserController::class]

// Доступ только к middleware
$middleware = $route->middleware; // [AuthMiddleware::class, ValidationMiddleware::class]

// Доступ к основному handler
$handler = $route->handler; // UserController::class
```

## Кеширование маршрутов

Для повышения производительности в продакшене:

### Создание кеша

```php
use Bermuda\Router\CacheFileProvider;

// Настройка маршрутов
$routes = new Routes();
$routes->addRoute(RouteRecord::get('home', '/', 'HomeController'));
$routes->addRoute(RouteRecord::get('users.show', '/users/[id]', 'UsersController::show'));

// Создание кеша
$cacheProvider = new CacheFileProvider('/path/to/cache');
$routeData = $routes->toArray();

$cacheProvider->writeFile('routes', $routeData);
```

### Использование кеша

```php
use Bermuda\Router\RoutesCache;

// Загрузка кешированных маршрутов
$cacheProvider = new CacheFileProvider('/path/to/cache');
$cachedData = $cacheProvider->readFile('routes');

$routes = new RoutesCache($cachedData);
$router = Router::fromDnf($routes);
```

### Кеш с контекстом для замыканий

Когда маршруты используют замыкания с внешними переменными (через `use`), эти переменные должны быть доступны при загрузке кешированных маршрутов. Контекст позволяет передать необходимые объекты и данные в область видимости кешированного файла.

```php
// При создании маршрутов с замыканиями
$app = new Application();
$db = new Database();

$routes->addRoute(RouteRecord::get('users.index', '/users', 
    function() use ($app, $db) {
        return $app->respond($db->users()->all());
    }
));

// Сохранение кеша с контекстом
$cacheProvider->writeFile('routes', $routes->toArray(), [
    'app' => $app,
    'db' => $db
]);

// Загрузка с контекстом - переменные $app и $db будут доступны в кешированном файле
$cachedData = $cacheProvider->readFile('routes');
$routes = new RoutesCache($cachedData);
```

### Ограничения кеширования

Система кеширования имеет следующие ограничения:

#### ❌ Что нельзя кешировать

```php
// 1. Объекты как обработчики
$controller = new UserController();
$routes->addRoute(RouteRecord::get('users', '/users', $controller)); // Не кешируется

// 2. Замыкания с объектами в use (без контекста)
$service = new UserService();
$routes->addRoute(RouteRecord::get('users', '/users', 
    function() use ($service) { // Объект $service не сериализуется
        return $service->getUsers();
    }
));

// 3. Ресурсы (файлы, соединения с БД)
$connection = fopen('data.txt', 'r');
$routes->addRoute(RouteRecord::get('data', '/data', 
    function() use ($connection) { // Ресурс не сериализуется
        return fread($connection, 1024);
    }
));

// 4. Анонимные классы
$routes->addRoute(RouteRecord::get('test', '/test', new class {
    public function handle() { return 'test'; }
})); // Анонимный класс не кешируется
```

#### ✅ Что можно кешировать

```php
// 1. Строки (имена классов и методов)
$routes->addRoute(RouteRecord::get('users', '/users', 'UserController'));
$routes->addRoute(RouteRecord::get('posts', '/posts', 'PostController::index'));

// 2. Массивы с именами классов/методов
$routes->addRoute(RouteRecord::get('api', '/api', ['ApiController', 'handle']));

// 3. Скалярные значения в контексте
$routes->addRoute(RouteRecord::get('config', '/config',
    function() use ($appName, $version) { // $appName и $version - строки/числа
        return ['app' => $appName, 'version' => $version];
    }
));
```

#### 💡 Рекомендации

1. **Используйте строковые обработчики** в продакшене для максимальной совместимости с кешем
2. **Передавайте простые данные** через контекст (строки, числа, массивы)
3. **Избегайте объектов в замыканиях** или обеспечьте их наличие в контексте
4. **Тестируйте кеширование** в среде, максимально приближенной к продакшену

## Обработка ошибок

### Типы исключений

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
    // 404 - маршрут не найден
    echo "Путь не найден: $e->path [$e->requestMethod]";
} catch (RouteNotRegisteredException $e) {
    // 500 - маршрут не зарегистрирован 
    echo "Маршрут '$e->routeName' не зарегистрирован";
} catch (GeneratorException $e) {
    // 400 - ошибка генерации URL
    echo "Ошибка генерации URL: " . $e->getMessage();
} catch (MatchException $e) {
    // Ошибка сопоставления паттернов
    echo "Ошибка сопоставления: $e->pattern для $e->path";
} catch (RouterException $e) {
    // Общие ошибки роутера
    echo "Ошибка роутера: " . $e->getMessage();
}
```
