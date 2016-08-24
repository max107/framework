[![Build Status](https://travis-ci.org/MindyPHP/Mindy_Event.svg?branch=master)](https://travis-ci.org/MindyPHP/Mindy_Event)

# Измененная логика в сравнении с оригинальным Aura\Signal

Если сигнал описан в массиве вида

```php
return [
    // Будет вызвана анонимная функция
    ['\Mindy\Controller\BaseController', 'beforeAction', function ($action) {
        var_dump(1);
    }],
    // Если класс is_a и сигнал идентичен, то будет вызвана одноименная функция в классе отправителе
    ['\Mindy\Controller\BaseController', 'beforeAction', ['\Mindy\Controller\BaseController', 'beforeAction']],
    // Метод класса Helper::fooBar будет вызван статически
    ['\Mindy\Controller\BaseController', 'beforeAction', ['\My\Super\Helper', 'fooBar']],
];
```
