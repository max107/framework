# Сессии

Создание сессии:
```php
[
    'class' => '\Mindy\Session\Session',
    'handler' => [
        'class' => '\Mindy\Session\Adapter\NativeSessionAdapter',
        'iniOptions' => [
            'save_path' => '...'
            // ...
        ]
    ]
]
```


Конфигурирование сессий:

**PHP**:
```php
[
    'class' => '\Mindy\Session\Adapter\NativeSessionAdapter',
]
```

**Redis**:
```php
[
    'class' => '\Mindy\Session\Adapter\RedisSessionAdapter',
    'servers' => [
        "tcp://127.0.0.1:6379"
    ]
]
```

**Memcached**:
```php
[
    'class' => '\Mindy\Session\Adapter\MemcachedSessionAdapter',
    'servers' => [
        "127.0.0.1:11211?persistent=1&weight=1&timeout=1&retry_interval=15"
    ]
]
```

**Database (PDO)**:
```php
[
    'class' => '\Mindy\Session\Adapter\PdoSessionAdapter',
    'pdo' => $pdo,
]
```

## Работа с сессиями

```php
// Возвращает bool в случае успешного выполнения
$session->set('key', 'value');
// Возвращает значение или значение по умолчанию если значение отсутствовало
$session->get('key', 'defaultValue');
// Удаление значения
$session->remove('key');
// Очищение данных в сессии
$session->clear();
// Возвращает количество данных в сессии int
$session->count();
count($session);
```