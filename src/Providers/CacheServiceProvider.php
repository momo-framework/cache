<?php

/**
 * Part of Momo Framework.
 *
 * © Momo Framework
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Unauthorized copying, modification, or distribution of this file,
 * via any medium, is strictly prohibited without prior written permission
 * from the copyright holder.
 *
 * @author    Vahe Sargsyan <w33bvGL>
 * @copyright Momo Framework
 * @license   AGPL-3.0-or-later <https://www.gnu.org/licenses/agpl-3.0.html>
 * @link      https://github.com/momo-framework
 */

declare(strict_types=1);

namespace Momo\Cache\Providers;

use Momo\Cache\Attributes\CachedReader;
use Momo\Cache\Cache;
use Momo\Cache\Contracts\CacheInterface;
use Momo\Cache\Contracts\CacheStoreInterface;
use Momo\Cache\Contracts\ClockInterface;
use Momo\Cache\Contracts\KeyHasherInterface;
use Momo\Cache\Hashing\KeyHasherFactory;
use Momo\Cache\Invalidation\CacheInvalidationListener;
use Momo\Cache\Invalidation\InvalidationMap;
use Momo\Cache\QueryCache;
use Momo\Cache\Store\ArrayStore;
use Momo\Cache\Store\StoreFactory;
use Momo\Cache\Support\SystemClock;
use Momo\Events\Contracts\DomainEventInterface;
use Momo\Events\Contracts\EventBusInterface;
use Momo\Kernel\Support\ServiceProvider;

/**
 * Wires the cache, key hasher, attribute reader and reactive invalidation into
 * the container, then subscribes the invalidation listener to the domain events
 * declared in the `cache.invalidation` config map.
 *
 * @codeCoverageIgnore
 */
final class CacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig(__DIR__ . '/../../config/cache.php', 'cache');

        $this->singleton(ClockInterface::class, static fn (): ClockInterface => new SystemClock());
        $this->singleton(KeyHasherInterface::class, static fn (): KeyHasherInterface => KeyHasherFactory::create());

        $this->singleton(
            CacheStoreInterface::class,
            fn (): CacheStoreInterface => $this->buildStore(),
        );

        $this->singleton(
            CacheInterface::class,
            fn (): CacheInterface => new Cache($this->store(), $this->hasher()),
        );

        $this->singleton(CachedReader::class, static fn (): CachedReader => new CachedReader());
        $this->singleton(InvalidationMap::class, static fn (): InvalidationMap => new InvalidationMap());

        $this->singleton(
            QueryCache::class,
            fn (): QueryCache => new QueryCache(
                $this->make(CacheInterface::class, CacheInterface::class),
                $this->make(CachedReader::class, CachedReader::class),
            ),
        );

        $this->singleton(
            CacheInvalidationListener::class,
            fn (): CacheInvalidationListener => new CacheInvalidationListener(
                $this->make(CacheInterface::class, CacheInterface::class),
                $this->make(InvalidationMap::class, InvalidationMap::class),
                $this->make(EventBusInterface::class, EventBusInterface::class),
            ),
        );
    }

    public function boot(): void
    {
        /** @var InvalidationMap $map */
        $map = $this->app->make(InvalidationMap::class);
        /** @var CacheInvalidationListener $listener */
        $listener = $this->app->make(CacheInvalidationListener::class);
        /** @var EventBusInterface $events */
        $events = $this->app->make(EventBusInterface::class);

        foreach ($this->invalidationConfig() as $eventClass => $tags) {
            $map->register($eventClass, $tags);
            $events->subscribe($eventClass, $listener);
        }
    }

    /**
     * @return array<class-string<DomainEventInterface>, list<non-empty-string>>
     */
    private function invalidationConfig(): array
    {
        $raw = $this->app->getConfig('cache.invalidation', []);

        if (! \is_array($raw)) {
            return [];
        }

        $result = [];
        foreach ($raw as $eventClass => $tags) {
            if (! \is_string($eventClass) || $eventClass === '' || ! \is_array($tags)) {
                continue;
            }

            if (! \is_subclass_of($eventClass, DomainEventInterface::class)) {
                continue;
            }

            $clean = [];
            foreach ($tags as $tag) {
                if (\is_string($tag) && $tag !== '') {
                    $clean[] = $tag;
                }
            }

            $result[$eventClass] = $clean;
        }

        return $result;
    }

    /**
     * @template T of object
     * @param class-string<T> $abstract
     * @param class-string<T> $expected
     * @return T
     */
    private function make(string $abstract, string $expected): object
    {
        $instance = $this->app->make($abstract);

        if (! $instance instanceof $expected) {
            throw new \RuntimeException(\sprintf('Container returned %s, expected %s.', \get_debug_type($instance), $expected));
        }

        return $instance;
    }

    private function buildStore(): CacheStoreInterface
    {
        $driver = $this->stringConfig('cache.store', 'array');

        if ($driver === '' || $driver === 'array') {
            return new ArrayStore($this->clock());
        }

        return StoreFactory::make($driver, $this->arrayConfig('cache.stores.' . $driver));
    }

    private function stringConfig(string $key, string $default): string
    {
        $value = $this->app->getConfig($key, $default);

        return \is_string($value) ? $value : $default;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function arrayConfig(string $key): array
    {
        $value = $this->app->getConfig($key, []);

        return \is_array($value) ? $value : [];
    }

    private function clock(): ClockInterface
    {
        return $this->make(ClockInterface::class, ClockInterface::class);
    }

    private function store(): CacheStoreInterface
    {
        return $this->make(CacheStoreInterface::class, CacheStoreInterface::class);
    }

    private function hasher(): KeyHasherInterface
    {
        return $this->make(KeyHasherInterface::class, KeyHasherInterface::class);
    }
}
