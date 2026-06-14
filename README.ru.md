<div align="center">
  <img src="https://avatars.githubusercontent.com/u/255415480?s=200&v=4" alt="Momo Framework" width="120" height="120" />

  <h1>momo-framework/cache</h1>

  <p>
      Реактивный кеш с тег-инвалидацией для <a href="https://github.com/momo-framework">Momo Framework</a>.
  </p>

  <p>
    <img src="https://github.com/momo-framework/cache/actions/workflows/ci.yml/badge.svg" alt="CI" />
    <img src="https://img.shields.io/packagist/v/momo-framework/cache.svg?style=flat" alt="Latest Version" />
    <img src="https://img.shields.io/packagist/dt/momo-framework/cache.svg?style=flat" alt="Total Downloads" />
    <img src="https://img.shields.io/badge/php-%3E%3D8.5-8892bf.svg" alt="PHP Version" />
    <img src="https://img.shields.io/badge/license-AGPL--3.0-blue.svg" alt="License" />
    <img src="https://img.shields.io/badge/coverage-100%25-brightgreen.svg" alt="Coverage" />
    <img src="https://img.shields.io/badge/PHPStan-level%2010-brightblue.svg" alt="PHPStan" />
  </p>
  <p>🇷🇺 Русская версия &nbsp;·&nbsp; 🇬🇧 <a href="README.md">English</a></p>
</div>

---

## Обзор

`momo-framework/cache` — кеширующий слой Momo Framework. Предоставляет единый `CacheInterface` с подключаемыми бэкендами (Array, Redis, Memcached) и реактивную систему инвалидации, которая связывает доменные события напрямую с вытеснением кеша — без опросов, без устаревших данных.

Ключи всегда проходят хеширование через `KeyHasherInterface` (по умолчанию xxHash) перед записью в хранилище, что обеспечивает чистое ключевое пространство независимо от формата логических ключей в приложении.

Пакет включает `QueryCache` — лёгкий декоратор для CQRS query handlers. Когда обработчик помечен атрибутом `#[Cached]`, его результат прозрачно кешируется и вытесняется в момент срабатывания соответствующего доменного события — обработчик ничего не знает о кешировании.

## Требования

- PHP >= 8.5
- Для Redis: `ext-redis`
- Для Memcached: `ext-memcached`
- Для xxHash: `ext-xxhash` (автоматически переключается на `md5`)

## Установка

```bash
composer require momo-framework/cache
```

## Ключевые концепции

### CacheInterface

Основной фасад. Все операции с кешем проходят через него:

```php
use Momo\Cache\Contracts\CacheInterface;

$cache->set('user:42', $user, ttl: 3600, tags: ['users']);
$cache->get('user:42');
$cache->has('user:42');
$cache->delete('user:42');

// Записать-или-вычислить за один вызов (типобезопасно через дженерики)
$user = $cache->remember('user:42', ttl: 3600, callback: fn () => $repo->find(42), tags: ['users']);

// Вытеснить все записи с тегом
$cache->invalidateTag('users');
$cache->invalidateTags(['users', 'products']);
```

### Хеширование ключей

Каждый логический ключ прогоняется через `KeyHasherInterface` перед записью в хранилище. Реализация по умолчанию использует xxHash (быстрый, некриптографический):

```php
use Momo\Cache\Contracts\KeyHasherInterface;

$hasher = $container->make(KeyHasherInterface::class);
$hashed = $hasher->hash('user:42:profile'); // e.g. "a3f1c9..."
```

Хешировать вручную не нужно — `Cache` делает это автоматически.

### Атрибут #[Cached]

Аннотируйте CQRS query handler, чтобы его результат кешировался автоматически:

```php
use Momo\Cache\Attributes\Cached;

#[Cached(ttl: 300, tags: ['products'])]
final class ListProductsHandler
{
    public function handle(ListProductsQuery $query): array
    {
        return $this->repository->findAll();
    }
}
```

`QueryCache` оборачивает вызов обработчика: при промахе — вызывает и кеширует результат, при попадании — возвращает кешированное значение немедленно.

### Реактивная инвалидация

Задайте привязку доменных событий к тегам в `config/cache.php`:

```php
'invalidation' => [
    \App\Catalog\Events\ProductUpdated::class => ['products'],
    \App\Catalog\Events\ProductDeleted::class => ['products'],
    \App\Orders\Events\OrderPlaced::class     => ['orders', 'inventory'],
],
```

`CacheInvalidationListener` подписывается на каждое зарегистрированное событие. Когда срабатывает `ProductUpdated`, все записи кеша с тегом `products` вытесняются автоматически без отдельной фоновой задачи.

Можно также строить карту программно:

```php
use Momo\Cache\Invalidation\InvalidationMap;

$map->register(ProductUpdated::class, ['products']);
$map->register(OrderPlaced::class, ['orders', 'inventory']);
```

## Использование

### Базовые операции get / set / remember

```php
use Momo\Cache\Cache;
use Momo\Cache\Store\ArrayStore;
use Momo\Cache\Hashing\PhpXxHashKeyHasher;

$store  = new ArrayStore();
$hasher = new PhpXxHashKeyHasher();
$cache  = new Cache($store, $hasher);

// Сохранить на 60 секунд, тег "users"
$cache->set('user:1', ['name' => 'Alice'], ttl: 60, tags: ['users']);

// Получить — null при промахе
$user = $cache->get('user:1');

// Записать-или-вычислить
$user = $cache->remember('user:1', ttl: 60, callback: function () use ($repo) {
    return $repo->findById(1);
}, tags: ['users']);
```

### Redis-хранилище

```php
use Momo\Cache\Store\RedisStore;
use Momo\Cache\Store\PhpRedisClient;

$redis = new PhpRedisClient(host: '127.0.0.1', port: 6379, prefix: 'momo');
$store = new RedisStore($redis);
```

### QueryCache в декораторе шины запросов

```php
use Momo\Cache\QueryCache;
use Momo\Cache\Attributes\CachedReader;

$queryCache = new QueryCache($cache, new CachedReader());

// Обернуть вызов обработчика в middleware шины:
$result = $queryCache->remember($handler, $query, fn () => $handler->handle($query));
```

## Конфигурация

`config/cache.php` публикуется автоматически через `CacheServiceProvider`:

```php
return [
    'store' => env('CACHE_STORE', 'array'), // array | redis | memcached

    'stores' => [
        'redis' => [
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'port'     => (int) env('REDIS_PORT', 6379),
            'database' => (int) env('REDIS_DB', 0),
            'password' => env('REDIS_PASSWORD', ''),
            'prefix'   => env('REDIS_PREFIX', 'momo'),
        ],
        'memcached' => [
            'servers' => [
                ['host' => env('MEMCACHED_HOST', '127.0.0.1'), 'port' => (int) env('MEMCACHED_PORT', 11211)],
            ],
            'prefix' => env('MEMCACHED_PREFIX', 'momo'),
        ],
    ],

    'default_ttl' => (int) env('MOMO_CACHE_TTL', 3600),

    // Привязка доменных событий к тегам (реактивная инвалидация)
    'invalidation' => [],
];
```

> **Swoole:** драйвер `array` изолирован на уровне воркера — подходит только для одного воркера или локального кеша. В многоворкерных prod-средах используйте `redis` для общего состояния.

## Разработка

```bash
composer lint          # Проверка стиля кода
composer stan          # PHPStan уровень 10
composer rector:check  # Rector dry-run
composer test          # Запуск тестов
composer ci            # Все проверки разом
```
