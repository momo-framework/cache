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

namespace Momo\Cache;

use Momo\Cache\Contracts\CacheInterface;
use Momo\Cache\Contracts\CacheStoreInterface;
use Momo\Cache\Contracts\KeyHasherInterface;

/**
 * High-level cache: hashes logical keys through the {@see KeyHasherInterface}
 * and delegates storage to a {@see CacheStoreInterface}.
 */
final class Cache implements CacheInterface
{
    public function __construct(
        private readonly CacheStoreInterface $store,
        private readonly KeyHasherInterface $hasher,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $entry = $this->store->get($this->hasher->hash($key));

        return $entry !== null ? $entry->value : $default;
    }

    public function set(string $key, mixed $value, ?int $ttl = null, array $tags = []): void
    {
        $this->store->put($this->hasher->hash($key), $value, $ttl, $tags);
    }

    public function has(string $key): bool
    {
        return $this->store->get($this->hasher->hash($key)) !== null;
    }

    public function delete(string $key): bool
    {
        return $this->store->forget($this->hasher->hash($key));
    }

    public function clear(): void
    {
        $this->store->flush();
    }

    /**
     * @template T
     * @param non-empty-string       $key
     * @param int<1, max>|null       $ttl
     * @param callable(): T          $callback
     * @param list<non-empty-string> $tags
     * @return T
     */
    public function remember(string $key, ?int $ttl, callable $callback, array $tags = []): mixed
    {
        $hashed = $this->hasher->hash($key);
        $entry  = $this->store->get($hashed);

        if ($entry !== null) {
            /** @var T $cached */
            $cached = $entry->value;

            return $cached;
        }

        $value = $callback();
        $this->store->put($hashed, $value, $ttl, $tags);

        return $value;
    }

    public function invalidateTag(string $tag): int
    {
        return $this->store->flushTag($tag);
    }

    public function invalidateTags(array $tags): int
    {
        $removed = 0;

        foreach ($tags as $tag) {
            $removed += $this->store->flushTag($tag);
        }

        return $removed;
    }
}
