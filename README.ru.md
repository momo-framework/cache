# momo/cache

Реактивный кэш с инвалидацией по тегам и **по доменным событиям** — результаты запросов остаются «тёплыми», пока доменное событие не сделает их устаревшими.

> 🇬🇧 Documentation in English: [README.md](README.md)

## Установка

```bash
composer require momo-framework/cache
```

`CacheServiceProvider` обнаруживается автоматически. Он связывает
`CacheInterface`, `CacheStoreInterface`, `KeyHasherInterface`, `QueryCache`,
`CachedReader`, `InvalidationMap` и реактивный `CacheInvalidationListener`.

## Быстрый старт

```php
// Кэширование read-through
$user = $cache->remember("user:{$id}", ttl: 60, callback: fn () => $repo->find($id), tags: ['users']);

// Обычные set/get
$cache->set('flag', true, ttl: 30);
$cache->get('flag', default: false);

// Инвалидация по тегу
$cache->invalidateTag('users');
```

## Query-хендлеры с `#[Cached]`

Пометьте хендлер атрибутом и оберните его вызов через `QueryCache`:

```php
use Momo\Cache\Attributes\Cached;

#[Cached(ttl: 60, tags: ['orders'])]
final class GetOrderHandler { /* ... */ }

// В декораторе query-шины:
$result = $queryCache->remember($handler, $query, fn () => $handler->handle($query));
```

Ключ кэша выводится из класса и значения запроса, поэтому разные запросы
кэшируются независимо.

## Реактивная инвалидация

Сопоставьте доменные события тегам в `config/cache.php`:

```php
'invalidation' => [
    \App\Orders\Events\OrderShipped::class => ['orders'],
],
```

Когда `OrderShipped` публикуется в шине событий Momo, листенер сбрасывает тег
`orders` и публикует событие `CacheInvalidated` — без ручного сброса кэша.

## Драйверы хранилища

Бэкенд абстрагирован за `CacheStoreInterface` и выбирается ключом конфига
`cache.store`:

| Драйвер     | Класс            | Область                        | Инвалидация по тегам        |
|-------------|------------------|--------------------------------|-----------------------------|
| `array`     | `ArrayStore`     | в рамках процесса (по умолчанию) | индекс тег → ключи        |
| `redis`     | `RedisStore`     | общий между воркерами/серверами | Redis-сеты (атомарный `SADD`) |
| `memcached` | `MemcachedStore` | общий между воркерами/серверами | список членов на тег         |

Долгоживущему многоворкерному Swoole-развёртыванию нужен **общий** store, чтобы
записи кэша и инвалидация тегов были видны между воркерами — рекомендуется
`redis`. `array` — только в рамках процесса.

```php
// config/cache.php
'store'  => 'redis',
'stores' => [
    'redis' => ['host' => '127.0.0.1', 'port' => 6379, 'database' => 0, 'prefix' => 'shop'],
],
```

Неблокирующий I/O: драйвер Redis работает через клиент-интерфейс — свяжите
адаптер phpredis под `Swoole\Runtime::enableCoroutine()` или нативный
`Swoole\Coroutine\Redis`, чтобы сокет-вызовы уступали планировщику. Поставляемые
адаптеры `PhpRedisClient` / `PhpMemcachedClient` требуют `ext-redis` /
`ext-memcached` и проверяются интеграционными CI-джобами; сама логика сторов
покрыта юнит-тестами через in-memory фейки.

## Нативное ускорение

Ключи кэша хешируются через xxh3. `KeyHasherFactory` выбирает нативный
ускоритель `momo_cache` при наличии (с совпадающим `MOMO_CACHE_EXT_VERSION`),
иначе — чистый PHP `PhpXxHashKeyHasher`.

## Документация

- [Обзор](docs/ru/overview.md)
- [Справочник API](docs/ru/api.md)
- [Конфигурация](docs/ru/configuration.md)

## Лицензия

AGPL-3.0-or-later.
