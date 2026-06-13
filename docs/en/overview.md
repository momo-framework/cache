# Overview

`momo/cache` keeps expensive read results warm on the 45k+ RPS hot path and —
its distinguishing feature — evicts them **reactively** when the underlying data
changes, by listening to domain events instead of relying on TTL guesswork or
scattered manual `forget()` calls.

## Layers

```
CacheInterface (Cache)         ← high-level: remember(), tags, generic typing
        │  hashes keys via KeyHasherInterface (xxh3 / native)
        ▼
CacheStoreInterface (ArrayStore)  ← low-level: TTL, tag index
        ▲
QueryCache + #[Cached]          ← DX: cache a query handler's result by attribute
        ▲
CacheInvalidationListener       ← reactive: domain event → flush tags
   (subscribed via InvalidationMap)
```

## Building blocks

| Concept        | Type                          | Responsibility                                  |
|----------------|-------------------------------|-------------------------------------------------|
| Cache facade   | `CacheInterface` / `Cache`    | `get/set/has/delete/clear/remember`, tag flush  |
| Store          | `CacheStoreInterface` / `ArrayStore` | TTL-aware key/value with a tag index     |
| Entry          | `CacheEntry`                  | value + absolute expiry                         |
| Key hasher     | `KeyHasherInterface`          | xxh3 digest; native-accelerated via factory     |
| `#[Cached]`    | attribute + `CachedReader`    | declarative result caching on a query handler   |
| Query cache    | `QueryCache`                  | applies `#[Cached]`, derives a key from the query |
| Invalidation   | `InvalidationMap` + `CacheInvalidationListener` | event → tags → flush          |

## Design decisions

- **Reactive over TTL-only.** TTL is a fallback; correctness comes from flushing
  tags the moment a domain event signals a change. A query result tagged
  `orders` survives until `OrderShipped` (or any mapped event) fires.
- **Generic `remember<T>()`.** The value slot is the single justified `mixed` in
  the package (caches are heterogeneous); `remember()` is generic so call sites
  keep full type safety. A cached `null` is a hit, not a miss.
- **Clock injection.** TTL expiry is checked against an injected
  `ClockInterface`, so expiry is deterministic under test (`MutableClock`).
- **Key hashing behind an interface.** xxh3 via ext-hash by default; a native
  `momo_cache` crate can replace it with no call-site change (factory + ABI
  check + safe fallback, mirroring `StockCounterFactory`).
- **Attribute-driven query caching.** `#[Cached(ttl, tags)]` plus `QueryCache`
  gives Laravel-style ergonomics with the tags wired straight into reactive
  invalidation.

## Coroutine & memory safety

`ArrayStore` holds state for the worker's lifetime, not per request — no
cross-request leak — and its methods contain no I/O suspension point, so they
are atomic with respect to sibling coroutines in one Swoole worker. Bind a Redis
or Swoole-Table `CacheStoreInterface` for cross-worker sharing; the tag index
contract carries over.
