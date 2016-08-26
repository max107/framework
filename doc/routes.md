# Роутер

Компонент основан на PHRoute. Изменения в сравнении с PHRoute:
* Убраны `filters`
* Добавлены `groupConfig`, `group`
* Добавлен класс `Patterns`

Инициализация диспетчера:

```php
$collector = new RouteCollector(new RouteParser());
$collector->addRoute(['GET', 'POST'], ['/', 'index'], ['Controller', 'actionIndex'], ['csrf' => false]);
// или
$collector->get(['/', 'index'], ['Controller', 'actionIndex'], ['csrf' => false]);
$collector->groupConfig('/blog', function() {

    return [
        [
            'route' => '/view/{id:i}',
            'name' => 'blog:view',
            'handler' => ['BlogController', 'actionView'],
        ]
    ];

});

$collector->group('/forum', function($collector) {

    $collector->get(['/topic/{slug}', 'forum:topic'], function($slug) {
        return 'foo';
    });

});

$dispatcher = new Dispatcher($collector);
$dispatcher->dispatch('GET', '/foo/bar');
```

Структура роутов:

```php
return [
    [
        // Роут
        'route' => '/',
        // Имя роута
        'name' => 'index',
        // Строка или массив с указанием списка разрешенных HTTP методов
        'method' => ['GET', 'POST'],
        // Метод который будет вызыван
        'handler' => ['Controller', 'actionIndex'],
        // Параметры передаются в handler как есть
        'params' => [
            'csrf' => false
        ]
    ],
    // Группа роутов
    '/blog' => [
        [
            // Итоговый роут будет выглядеть как: /blog/view/1
            'route' => '/view/{id:i}',
            'name' => 'blog:view',
            'handler' => ['BlogController', 'actionView'],
        ]
    ]
];
```

Использование как микрофреймворка:

```php
$collector = new RouteCollector(new RouteParser());
$collector->get(['/', 'index'], function () {
    return 'index';
});
$collector->group('/blog', function($groupCollector) {
    $groupCollector->get(['/view/{id:i}', 'blog:view'], function($id) {
        return 'blog post: ' . $id;
    });
});

$collector->group('/forum', function($collector) {
    $collector->get(['/topic/{slug}', 'forum:topic'], function($slug) {
        return 'foo';
    });
});

$dispatcher = new Dispatcher($collector);
$output = $dispatcher->dispatch('GET', '/foo/bar');

echo $output;
```