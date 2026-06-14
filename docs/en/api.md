# API Reference

## Contracts (`Momo\Cache\Contracts`)

### `CacheInterface`
- `get(non-empty-string $key, mixed $default = null): mixed`
- `set(non-empty-string $key, mixed $value, ?int $ttl = null, list<non-empty-string> $tags = []): void`
- `has(non-empty-string $key): bool`
- `delete(non-empty-string $key): bool`
- `clear(): void`
- `remember<T>(non-empty-string $key, ?int $ttl, callable(): T $callback, list<non-empty-string> $tags = []): T`
- `invalidateTag(non-empty-string $tag): int<0, max>`
- `invalidateTags(list<non-empty-string> $tags): int<0, max>`

### `CacheStoreInterface`
- `get(non-empty-string $key): ?CacheEntry` — null when missing **or expired**.
- `put(non-empty-string $key, mixed $value, int<1,max>|null $ttlSeconds, list<non-empty-string> $tags): void`
- `forget(non-empty-string $key): bool`
- `flush(): void`
- `flushTag(non-empty-string $tag): int<0, max>`

### `KeyHasherInterface`
- `hash(non-empty-string $key): non-empty-string`.

### `ClockInterface`
- `now(): int<0, max>`.

## Cache (`Momo\Cache`)

### `Cache`
Implements `CacheInterface`; hashes keys then delegates to a store. A cached
`null` is returned as a hit (the default only applies on a genuine miss).

### `QueryCache`
- `remember<T>(object $handler, object $query, callable(): T $compute): T` — if
  the handler class carries `#[Cached]`, caches `$compute()` under a key derived
  from the handler and query class + serialised query value, with the
  attribute's TTL and tags; otherwise runs `$compute()` uncached.

## Store (`Momo\Cache\Store`)
- `CacheEntry` *(final readonly)* — `value`, `expiresAt`; `isExpired(int $now): bool`.
- `ArrayStore` — process-local store with TTL and a tag → keys index.
- `RedisStore(RedisClientInterface $client, non-empty-string $namespace = 'momo')` —
  shared store; tags via Redis sets.
- `MemcachedStore(MemcachedClientInterface $client, non-empty-string $namespace = 'momo')` —
  shared store; tags via per-tag member list.
- `RedisClientInterface` / `MemcachedClientInterface` — minimal command surfaces
  the stores depend on (fake-tested).
- `PhpRedisClient` / `PhpMemcachedClient` — ext-redis / ext-memcached adapters
  (UNVERIFIED; require the extensions, verified in CI).
- `StoreFactory::make(non-empty-string $driver, array<array-key, mixed> $config): CacheStoreInterface`
  — builds a Redis/Memcached store from config (UNVERIFIED).

## Hashing (`Momo\Cache\Hashing`)
- `PhpXxHashKeyHasher` — `hash('xxh3', …)`.
- `KeyHasherFactory::create(): KeyHasherInterface`, `nativeIsUsable(): bool`,
  `SUPPORTED_ABI`.

## Attributes (`Momo\Cache\Attributes`)
- `#[Cached(?int $ttl = null, list<non-empty-string> $tags = [])]` — class attribute.
- `CachedReader::forClass(class-string): ?Cached` — memoised lookup.

## Invalidation (`Momo\Cache\Invalidation`)
- `InvalidationMap` — `register(class-string<DomainEventInterface>, list<non-empty-string>)`,
  `tagsFor(DomainEventInterface): list<non-empty-string>` (de-duplicated).
- `CacheInvalidationListener` — `Momo\Events\Contracts\EventListenerInterface`;
  on a mapped event, flushes its tags and publishes `CacheInvalidated`.

## Events (`Momo\Cache\Events`)
- `CacheInvalidated` extends `Momo\Events\DomainEvent`; carries `trigger`
  (event class), `tags`, `removed` count.

## Support (`Momo\Cache\Support`)
- `SystemClock`, `MutableClock`.
