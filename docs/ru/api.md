# Справочник API

## Контракты (`Momo\Cache\Contracts`)

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
- `get(non-empty-string $key): ?CacheEntry` — null при отсутствии **или истечении**.
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
Реализует `CacheInterface`; хеширует ключи и делегирует store. Кэшированный
`null` возвращается как попадание (default применяется только при настоящем промахе).

### `QueryCache`
- `remember<T>(object $handler, object $query, callable(): T $compute): T` — если
  класс хендлера несёт `#[Cached]`, кэширует `$compute()` под ключом из классов
  хендлера и запроса + сериализованного значения запроса, с TTL и тегами
  атрибута; иначе выполняет `$compute()` без кэша.

## Store (`Momo\Cache\Store`)
- `CacheEntry` *(final readonly)* — `value`, `expiresAt`; `isExpired(int $now): bool`.
- `ArrayStore` — store в рамках процесса с TTL и индексом тег → ключи.
- `RedisStore(RedisClientInterface $client, non-empty-string $namespace = 'momo')` —
  общий store; теги через Redis-сеты.
- `MemcachedStore(MemcachedClientInterface $client, non-empty-string $namespace = 'momo')` —
  общий store; теги через список членов на тег.
- `RedisClientInterface` / `MemcachedClientInterface` — минимальные наборы команд,
  от которых зависят сторы (покрыты фейками).
- `PhpRedisClient` / `PhpMemcachedClient` — адаптеры ext-redis / ext-memcached
  (UNVERIFIED; требуют расширений, проверяются в CI).
- `StoreFactory::make(non-empty-string $driver, array<array-key, mixed> $config): CacheStoreInterface`
  — строит Redis/Memcached store из конфига (UNVERIFIED).

## Hashing (`Momo\Cache\Hashing`)
- `PhpXxHashKeyHasher` — `hash('xxh3', …)`.
- `KeyHasherFactory::create(): KeyHasherInterface`, `nativeIsUsable(): bool`,
  `SUPPORTED_ABI`.

## Attributes (`Momo\Cache\Attributes`)
- `#[Cached(?int $ttl = null, list<non-empty-string> $tags = [])]` — атрибут класса.
- `CachedReader::forClass(class-string): ?Cached` — мемоизированный поиск.

## Invalidation (`Momo\Cache\Invalidation`)
- `InvalidationMap` — `register(class-string<DomainEventInterface>, list<non-empty-string>)`,
  `tagsFor(DomainEventInterface): list<non-empty-string>` (дедуплицировано).
- `CacheInvalidationListener` — `Momo\Events\Contracts\EventListenerInterface`;
  на сопоставленное событие сбрасывает его теги и публикует `CacheInvalidated`.

## События (`Momo\Cache\Events`)
- `CacheInvalidated` расширяет `Momo\Events\DomainEvent`; несёт `trigger`
  (класс события), `tags`, число `removed`.

## Support (`Momo\Cache\Support`)
- `SystemClock`, `MutableClock`.
