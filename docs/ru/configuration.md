# Конфигурация

Значения по умолчанию в `config/cache.php`, мёржатся под ключом `cache`.
Опубликуйте и переопределите их в `config/cache.php` приложения.

| Ключ           | Тип                                                        | По умолчанию | Значение                                         |
|----------------|------------------------------------------------------------|--------------|--------------------------------------------------|
| `store`        | `'array'\|'redis'\|'memcached'`                            | `array`      | Активный драйвер бэкенда.                          |
| `stores`       | `array<string, array<string, mixed>>`                     | дефолты redis/memcached | Настройки подключения на драйвер.    |
| `default_ttl`  | `int<1, max>\|null`                                        | `3600`       | TTL, если вызывающий не передал; null = без срока. |
| `invalidation` | `array<class-string<DomainEventInterface>, list<non-empty-string>>` | `[]` | Класс события → теги для сброса при его срабатывании. |

## Драйверы хранилища

`CacheServiceProvider` связывает `CacheStoreInterface` из `cache.store`:

- **`array`** (по умолчанию) — `ArrayStore`, в рамках процесса. Подходит для
  одного воркера, CLI и тестов. Не разделяется между воркерами.
- **`redis`** — `RedisStore` поверх `RedisClientInterface`. Разделяется между
  воркерами и серверами; теги через Redis-сеты (атомарный `SADD`), TTL
  обеспечивает Redis.
- **`memcached`** — `MemcachedStore` поверх `MemcachedClientInterface`.
  Разделяется; теги через список членов на тег (read-modify-write — см. заметку
  про теги в докблоке класса; для строгой инвалидации под нагрузкой
  предпочтительнее Redis).

```php
// config/cache.php
'store'  => 'redis',
'stores' => [
    'redis' => [
        'host'     => '127.0.0.1',
        'port'     => 6379,
        'database' => 0,
        'password' => '',
        'prefix'   => 'momo',   // namespace ключей, используется и в flush()
    ],
    'memcached' => [
        'servers' => [['host' => '127.0.0.1', 'port' => 11211]],
        'prefix'  => 'momo',
    ],
],
```

Адаптеры `PhpRedisClient` / `PhpMemcachedClient` требуют `ext-redis` /
`ext-memcached`. Для неблокирующего I/O под Swoole включите
`Swoole\Runtime::enableCoroutine()` (phpredis) или свяжите корутинный
Redis-клиент.

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
