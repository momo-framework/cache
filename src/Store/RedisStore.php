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

/**
 * Cross-worker cache store backed by Redis.
 *
 * TTL is enforced natively by Redis (SET ... EX), so an expired key is simply
 * absent on read. Tags use Redis sets: each tagged key is added to `tag:{tag}`,
 * and {@see flushTag()} reads the set and deletes its members. Values are
 * serialised with PHP `serialize()`; stale set members left by a plain
 * {@see forget()} are harmless — they delete to a no-op during a tag flush.
 *
 * NON-BLOCKING I/O: bind a client whose socket calls are coroutine-aware (a
 * phpredis adapter under `Swoole\Runtime::enableCoroutine()`, or a native
 * `Swoole\Coroutine\Redis`). The store itself performs no blocking work.
 */
final class RedisStore implements CacheStoreInterface
{
    /**
     * @param non-empty-string $namespace key prefix isolating this app's entries
     */
    public function __construct(
        private readonly RedisClientInterface $client,
        private readonly string $namespace = 'momo',
    ) {
    }

    public function get(string $key): ?CacheEntry
    {
        $raw = $this->client->get($this->key($key));

        if ($raw === null) {
            return null;
        }

        return new CacheEntry($this->unserialize($raw));
    }

    public function put(string $key, mixed $value, ?int $ttlSeconds, array $tags): void
    {
        $prefixed = $this->key($key);

        $this->client->set($prefixed, \serialize($value), $ttlSeconds);

        foreach ($tags as $tag) {
            $this->client->sadd($this->tagSet($tag), $prefixed);
        }
    }

    public function forget(string $key): bool
    {
        return $this->client->del($this->key($key));
    }

    public function flush(): void
    {
        foreach ($this->client->keys($this->namespace . ':*') as $key) {
            $this->client->del($key);
        }
    }

    public function flushTag(string $tag): int
    {
        $set     = $this->tagSet($tag);
        $removed = 0;

        foreach ($this->client->smembers($set) as $member) {
            if ($this->client->del($member)) {
                $removed++;
            }
        }

        $this->client->del($set);

        return $removed;
    }

    /**
     * @param non-empty-string $key
     * @return non-empty-string
     */
    private function key(string $key): string
    {
        return $this->namespace . ':' . $key;
    }

    /**
     * @param non-empty-string $tag
     * @return non-empty-string
     */
    private function tagSet(string $tag): string
    {
        return $this->namespace . ':tag:' . $tag;
    }

    private function unserialize(string $raw): mixed
    {
        return \unserialize($raw);
    }
}
