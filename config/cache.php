<?php

/**
 * Part of Momo Framework.
 *
 * © Momo Framework
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author    Vahe Sargsyan <w33bvGL>
 * @copyright Momo Framework
 * @license   AGPL-3.0-or-later <https://www.gnu.org/licenses/agpl-3.0.html>
 * @link      https://github.com/momo-framework
 */

declare(strict_types=1);

return array(

    // › Active Store Driver
    // ─────────────────────────────────────────────────────────────────── ⚛ ───
    //   Defines the primary storage engine blueprint for application caching.
    //   In a multi-worker Swoole environment, this choice dictates whether
    //   cache states are isolated per process memory space or globally shared.
    //
    //   ⚠ Using the "array" driver within a high-concurrency Swoole deployment
    //   leads to state drift across isolated worker processes, as mutations
    //   on one worker are completely invisible to concurrent coroutines running
    //   on sister instances. Use "redis" for absolute state consistency.
    //
    //   Supported Options:
    //     • "array"     - Ephemeral, in-memory process storage. Highly volatile.
    //     • "redis"     - Shared external storage. Ideal for multi-worker runtimes.
    //     • "memcached" - Distributed high-throughput memory object caching system.
    //

    'store' => env('CACHE_STORE', 'array'),

    // › Connection Settings Per Shared Driver
    // ─────────────────────────────────────────────────────────────────── ⚛ ───
    //   Configures low-level connection parameters utilized by StoreFactory
    //   to initialize backend storage connections. When executing under Swoole,
    //   these configurations directly interact with the framework's internal
    //   connection pooling engine, turning blocking I/O into non-blocking,
    //   coroutine-aware networking hooks.
    //
    //   Supported Options:
    //     • "array" - Multi-dimensional array structures defining host, port,
    //                 passwords, and specific connection pooling capacities.
    //

    'stores' => array(
        'redis' => array(
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'port'     => (int) env('REDIS_PORT', 6379),
            'database' => (int) env('REDIS_DB', 0),
            'password' => env('REDIS_PASSWORD', ''),
            'prefix'   => env('REDIS_PREFIX', 'momo'),
        ),
        'memcached' => array(
            'servers' => array(
                array(
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => (int) env('MEMCACHED_PORT', 11211),
                ),
            ),
            'prefix' => env('MEMCACHED_PREFIX', 'momo'),
        ),
    ),

    // › Default Time-To-Live
    // ─────────────────────────────────────────────────────────────────── ⚛ ───
    //   Establishes the default temporal lifespan (in seconds) for compiled
    //   cache entries when an explicit expiration parameter is omitted during
    //   the storage invocation.
    //
    //   ⚠ Setting this value to null will cause cache entries to persist
    //   indefinitely within the storage driver, which can lead to severe
    //   memory leak vectors and exhaustion of RAM pools in long-lived systems.
    //
    //   Supported Options:
    //     • "int"  - Exact duration in seconds before automated record eviction.
    //     • "null" - Infinite lifetime. Postpones eviction until manual purge.
    //

    'default_ttl' => (int) env('MOMO_CACHE_TTL', 3600),

    // › Reactive Invalidation Map
    // ─────────────────────────────────────────────────────────────────── ⚛ ───
    //   Registers an architectural binding map linking asynchronous Domain Events
    //   to explicit cache tags. The framework event listener intercepts these
    //   dispatched events inside the coroutine event loop and programmatically
    //   flushes corresponding cached data layers to prevent stale state reads.
    //
    //   Supported Options:
    //     • "array" - Associative map where keys are fully qualified class names
    //                 and values are arrays of targeting cache tags.
    //

    'invalidation' => array(),

);