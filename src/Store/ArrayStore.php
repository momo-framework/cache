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

namespace Momo\Cache\Store;

use Momo\Cache\Contracts\CacheStoreInterface;
use Momo\Cache\Contracts\ClockInterface;

/**
 * Process-local cache store with TTL and tag indexing.
 *
 * COROUTINE SAFETY: every method completes without an I/O suspension point, so
 * under Swoole's cooperative scheduler reads and writes are atomic with respect
 * to sibling coroutines in the same worker. State lives for the worker's
 * lifetime, never per request, so there is no cross-request leak. Bind a Redis
 * or Swoole-Table store for cross-worker sharing.
 */
final class ArrayStore implements CacheStoreInterface
{
    /** @var array<non-empty-string, CacheEntry> */
    private array $entries = [];

    /** @var array<non-empty-string, array<non-empty-string, true>> tag => set of keys */
    private array $tagIndex = [];

    public function __construct(
        private readonly ClockInterface $clock,
    ) {
    }

    public function get(string $key): ?CacheEntry
    {
        $entry = $this->entries[$key] ?? null;

        if ($entry === null) {
            return null;
        }

        if ($entry->isExpired($this->clock->now())) {
            $this->forget($key);

            return null;
        }

        return $entry;
    }

    public function put(string $key, mixed $value, ?int $ttlSeconds, array $tags): void
    {
        $expiresAt = $ttlSeconds === null ? null : $this->clock->now() + $ttlSeconds;

        $this->entries[$key] = new CacheEntry($value, $expiresAt);

        foreach ($tags as $tag) {
            $this->tagIndex[$tag][$key] = true;
        }
    }

    public function forget(string $key): bool
    {
        if (! isset($this->entries[$key])) {
            return false;
        }

        unset($this->entries[$key]);

        foreach ($this->tagIndex as $tag => $keys) {
            unset($this->tagIndex[$tag][$key]);
        }

        return true;
    }

    public function flush(): void
    {
        $this->entries  = [];
        $this->tagIndex = [];
    }

    public function flushTag(string $tag): int
    {
        $removed = 0;

        foreach (\array_keys($this->tagIndex[$tag] ?? []) as $key) {
            if (isset($this->entries[$key])) {
                unset($this->entries[$key]);
                $removed++;
            }
        }

        unset($this->tagIndex[$tag]);

        return $removed;
    }
}
