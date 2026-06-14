<div align="center">
  <img src="https://avatars.githubusercontent.com/u/255415480?s=200&v=4" alt="Momo Framework" width="120" height="120" />

  <h1>momo-framework/cache</h1>

  <p>
      Reactive cache with tag-based invalidation for <a href="https://github.com/momo-framework">Momo Framework</a>.
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
  <p>🇬🇧 English &nbsp;·&nbsp; 🇷🇺 <a href="README.ru.md">Русская версия</a></p>
</div>

---

## Overview

`momo-framework/cache` is the caching layer for Momo Framework. It provides a unified `CacheInterface` backed by pluggable store drivers (Array, Redis, Memcached) and exposes a reactive invalidation system that wires domain events directly to cache eviction — no polling, no stale reads.

Keys are always hashed through a `KeyHasherInterface` (xxHash by default) before reaching the store, keeping backend key spaces clean and collision-resistant regardless of the logical key format your application uses.

The package ships with `QueryCache`, a lightweight decorator for CQRS query handlers. When a handler is annotated with `#[Cached]`, its result is transparently memoised and evicted the moment a relevant domain event fires — without the handler needing to know anything about caching.

## Requirements

- PHP >= 8.5
- For Redis store: `ext-redis`
- For Memcached store: `ext-memcached`
- For xxHash: `ext-xxhash` (falls back to `md5` automatically)

## Installation

```bash
composer require momo-framework/cache
```

## Core Concepts

### CacheInterface

The primary facade. All cache operations go through it:

```php
use Momo\Cache\Contracts\CacheInterface;

$cache->set('user:42', $user, ttl: 3600, tags: ['users']);
$cache->get('user:42');
$cache->has('user:42');
$cache->delete('user:42');

// Store-or-compute in one call (type-safe via generics)
$user = $cache->remember('user:42', ttl: 3600, callback: fn () => $repo->find(42), tags: ['users']);

// Evict all entries carrying a tag
$cache->invalidateTag('users');
$cache->invalidateTags(['users', 'products']);
```

### Key Hashing

Every logical key is run through `KeyHasherInterface` before hitting the store. The default implementation uses xxHash (fast, non-cryptographic):

```php
use Momo\Cache\Contracts\KeyHasherInterface;

$hasher = $container->make(KeyHasherInterface::class);
$hashed = $hasher->hash('user:42:profile'); // e.g. "a3f1c9..."
```

You never need to hash manually — `Cache` does it for you.

### #[Cached] Attribute

Annotate a CQRS query handler to cache its result automatically:

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

`QueryCache` wraps handler dispatch: on a cache miss it calls the handler and stores the result; on a hit it returns the cached value immediately.

### Reactive Invalidation

Map domain events to cache tags in `config/cache.php`:

```php
'invalidation' => [
    \App\Catalog\Events\ProductUpdated::class => ['products'],
    \App\Catalog\Events\ProductDeleted::class => ['products'],
    \App\Orders\Events\OrderPlaced::class     => ['orders', 'inventory'],
],
```

`CacheInvalidationListener` subscribes to each registered event. When `ProductUpdated` fires, every cache entry tagged `products` is flushed automatically without a separate job.

You can also build the map programmatically:

```php
use Momo\Cache\Invalidation\InvalidationMap;

$map->register(ProductUpdated::class, ['products']);
$map->register(OrderPlaced::class, ['orders', 'inventory']);
```

## Usage

### Basic get / set / remember

```php
use Momo\Cache\Cache;
use Momo\Cache\Store\ArrayStore;
use Momo\Cache\Hashing\PhpXxHashKeyHasher;

$store  = new ArrayStore();
$hasher = new PhpXxHashKeyHasher();
$cache  = new Cache($store, $hasher);

// Store for 60 seconds, tagged "users"
$cache->set('user:1', ['name' => 'Alice'], ttl: 60, tags: ['users']);

// Fetch — returns null on miss
$user = $cache->get('user:1');

// Store-or-compute
$user = $cache->remember('user:1', ttl: 60, callback: function () use ($repo) {
    return $repo->findById(1);
}, tags: ['users']);
```

### Redis store

```php
use Momo\Cache\Store\RedisStore;
use Momo\Cache\Store\PhpRedisClient;

$redis = new PhpRedisClient(host: '127.0.0.1', port: 6379, prefix: 'momo');
$store = new RedisStore($redis);
```

### QueryCache with a bus decorator

```php
use Momo\Cache\QueryCache;
use Momo\Cache\Attributes\CachedReader;

$queryCache = new QueryCache($cache, new CachedReader());

// Wrap handler dispatch in your query bus middleware:
$result = $queryCache->remember($handler, $query, fn () => $handler->handle($query));
```

## Configuration

`config/cache.php` is published automatically by `CacheServiceProvider`:

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

    // Domain event → cache tag mapping (reactive invalidation)
    'invalidation' => [],
];
```

> **Swoole note:** the `array` store is per-worker and is suitable only for single-worker setups or local caches. Use `redis` in multi-worker production deployments to share state across workers.

## Development

```bash
composer lint          # Code style check
composer stan          # PHPStan level 10
composer rector:check  # Rector dry-run
composer test          # Run tests
composer ci            # Run all checks
```
