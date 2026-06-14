# Configuration

Defaults live in `config/cache.php`, merged under the `cache` key. Publish and
override them in your application's `config/cache.php`.

| Key            | Type                                                        | Default | Meaning                                          |
|----------------|------------------------------------------------------------|---------|--------------------------------------------------|
| `store`        | `'array'\|'redis'\|'memcached'`                             | `array` | Active backend driver.                            |
| `stores`       | `array<string, array<string, mixed>>`                      | redis/memcached defaults | Per-driver connection settings.   |
| `default_ttl`  | `int<1, max>\|null`                                         | `3600`  | TTL applied when a caller passes none; null = no expiry. |
| `invalidation` | `array<class-string<DomainEventInterface>, list<non-empty-string>>` | `[]` | Event class → tags to flush when it fires.       |

## Store drivers

`CacheServiceProvider` binds `CacheStoreInterface` from `cache.store`:

- **`array`** (default) — `ArrayStore`, per-process. Fine for a single worker,
  CLI, and tests. Not shared across workers.
- **`redis`** — `RedisStore` over `RedisClientInterface`. Shared across workers
  and servers; tags use Redis sets (atomic `SADD`), TTL enforced by Redis.
- **`memcached`** — `MemcachedStore` over `MemcachedClientInterface`. Shared;
  tags use a per-tag member list (read-modify-write — see the tag caveat in the
  class docblock; prefer Redis for strict invalidation under contention).

```php
// config/cache.php
'store'  => 'redis',
'stores' => [
    'redis' => [
        'host'     => '127.0.0.1',
        'port'     => 6379,
        'database' => 0,
        'password' => '',
        'prefix'   => 'momo',   // key namespace, also used by flush()
    ],
    'memcached' => [
        'servers' => [['host' => '127.0.0.1', 'port' => 11211]],
        'prefix'  => 'momo',
    ],
],
```

The `PhpRedisClient` / `PhpMemcachedClient` adapters require `ext-redis` /
`ext-memcached`. For non-blocking I/O under Swoole, enable
`Swoole\Runtime::enableCoroutine()` (phpredis) or bind a coroutine Redis client.

## Reactive invalidation map

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

At boot, `CacheServiceProvider` registers each entry in the `InvalidationMap`
and subscribes `CacheInvalidationListener` to the event on the Momo event bus.
Entries whose key is not a `DomainEventInterface`, or whose tags are not
non-empty strings, are skipped.

## Swappable bindings

| Interface              | Default                  |
|------------------------|--------------------------|
| `CacheInterface`       | `Cache`                  |
| `CacheStoreInterface`  | `ArrayStore`             |
| `KeyHasherInterface`   | `PhpXxHashKeyHasher` (or native `momo_cache`) |
| `ClockInterface`       | `SystemClock`            |

### Production store

Bind a Redis- or Swoole-Table-backed `CacheStoreInterface` for cross-worker
sharing and durability. Honour the tag-index contract so `flushTag()` evicts
correctly across workers.

### `#[Cached]` query handlers

`QueryCache` is bound and ready; wire it into a query-bus decorator so annotated
handlers cache transparently. Tags declared on `#[Cached]` participate in the
same reactive invalidation map.
