# momo/cache

Reactive cache with tag-based and **event-driven** invalidation — query results stay warm until a domain event makes them stale.

> 🇷🇺 Документация на русском: [README.ru.md](README.ru.md)

## Installation

```bash
composer require momo-framework/cache
```

`CacheServiceProvider` is auto-discovered. It binds `CacheInterface`,
`CacheStoreInterface`, `KeyHasherInterface`, `QueryCache`, the `CachedReader`,
the `InvalidationMap` and the reactive `CacheInvalidationListener`.

## Quick Start

```php
// Read-through caching
$user = $cache->remember("user:{$id}", ttl: 60, callback: fn () => $repo->find($id), tags: ['users']);

// Plain set/get
$cache->set('flag', true, ttl: 30);
$cache->get('flag', default: false);

// Tag invalidation
$cache->invalidateTag('users');
```

## `#[Cached]` query handlers

Annotate a query handler and wrap its dispatch with `QueryCache`:

```php
use Momo\Cache\Attributes\Cached;

#[Cached(ttl: 60, tags: ['orders'])]
final class GetOrderHandler { /* ... */ }

// In a query-bus decorator:
$result = $queryCache->remember($handler, $query, fn () => $handler->handle($query));
```

The cache key is derived from the query's class and value, so distinct queries
cache independently.

## Reactive invalidation

Map domain events to tags in `config/cache.php`:

```php
'invalidation' => [
    \App\Orders\Events\OrderShipped::class => ['orders'],
],
```

When `OrderShipped` is published on the Momo event bus, the listener flushes the
`orders` tag and emits a `CacheInvalidated` event — no manual cache busting.

## Native acceleration

Cache keys are hashed with xxh3. `KeyHasherFactory` selects a native
`momo_cache` accelerator when present (matching `MOMO_CACHE_EXT_VERSION`),
falling back to the pure-PHP `PhpXxHashKeyHasher` otherwise.

## Documentation

- [Overview](docs/en/overview.md)
- [API reference](docs/en/api.md)
- [Configuration](docs/en/configuration.md)

## License

AGPL-3.0-or-later.
