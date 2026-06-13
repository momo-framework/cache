# Конфигурация

Значения по умолчанию в `config/cache.php`, мёржатся под ключом `cache`.
Опубликуйте и переопределите их в `config/cache.php` приложения.

| Ключ           | Тип                                                        | По умолчанию | Значение                                         |
|----------------|------------------------------------------------------------|--------------|--------------------------------------------------|
| `default_ttl`  | `int<1, max>\|null`                                        | `3600`       | TTL, если вызывающий не передал; null = без срока. |
| `invalidation` | `array<class-string<DomainEventInterface>, list<non-empty-string>>` | `[]` | Класс события → теги для сброса при его срабатывании. |

## Карта реактивной инвалидации

```php
// config/cache.php
return [
    'default_ttl'  => 3600,
    'invalidation' => [
        \App\Orders\Events\OrderShipped::class   => ['orders', 'dashboard'],
        \App\Orders\Events\OrderCancelled::class => ['orders'],
    ],
];
```

На boot `CacheServiceProvider` регистрирует каждую запись в `InvalidationMap` и
подписывает `CacheInvalidationListener` на событие в шине Momo. Записи, чей ключ
не является `DomainEventInterface` или чьи теги не являются непустыми строками,
пропускаются.

## Подменяемые биндинги

| Интерфейс              | По умолчанию             |
|------------------------|--------------------------|
| `CacheInterface`       | `Cache`                  |
| `CacheStoreInterface`  | `ArrayStore`             |
| `KeyHasherInterface`   | `PhpXxHashKeyHasher` (или нативный `momo_cache`) |
| `ClockInterface`       | `SystemClock`            |

### Store для прода

Свяжите `CacheStoreInterface` на базе Redis или Swoole-Table для межворкерного
шеринга и устойчивости. Соблюдайте контракт индекса тегов, чтобы `flushTag()`
корректно вытеснял записи между воркерами.

### Query-хендлеры с `#[Cached]`

`QueryCache` связан и готов; заведите его в декоратор query-шины, чтобы
помеченные хендлеры кэшировались прозрачно. Теги из `#[Cached]` участвуют в той
же карте реактивной инвалидации.
