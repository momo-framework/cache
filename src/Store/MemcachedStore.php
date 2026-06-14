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
 * Cross-worker cache store backed by Memcached.
 *
 * Memcached has no native sets, so each tag keeps a serialised member list
 * under `tag:{tag}`; {@see flushTag()} reads the list and deletes its members.
 *
 * TAG CAVEAT: the member list is maintained with a read-modify-write, so under
 * heavy cross-node concurrency a racing writer can drop a key from a tag list —
 * that key then survives a tag flush until its own TTL expires. For strict
 * tag invalidation under contention prefer {@see RedisStore} (atomic SADD).
 * {@see flush()} clears the whole Memcached instance.
 */
final class MemcachedStore implements CacheStoreInterface
{
    /**
     * @param non-empty-string $namespace key prefix isolating this app's entries
     */
    public function __construct(
        private readonly MemcachedClientInterface $client,
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
            $listKey       = $this->tagList($tag);
            $members       = $this->readMembers($listKey);
            $members[$prefixed] = true;
            $this->client->set($listKey, \serialize($members), null);
        }
    }

    public function forget(string $key): bool
    {
        return $this->client->delete($this->key($key));
    }

    public function flush(): void
    {
        $this->client->flush();
    }

    public function flushTag(string $tag): int
    {
        $listKey = $this->tagList($tag);
        $removed = 0;

        foreach (\array_keys($this->readMembers($listKey)) as $member) {
            if ($this->client->delete($member)) {
                $removed++;
            }
        }

        $this->client->delete($listKey);

        return $removed;
    }

    /**
     * @param non-empty-string $listKey
     * @return array<non-empty-string, true>
     */
    private function readMembers(string $listKey): array
    {
        $raw = $this->client->get($listKey);

        if ($raw === null) {
            return [];
        }

        $decoded = $this->unserialize($raw);

        if (! \is_array($decoded)) {
            return [];
        }

        $members = [];
        foreach ($decoded as $member => $_present) {
            if (\is_string($member) && $member !== '') {
                $members[$member] = true;
            }
        }

        return $members;
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
    private function tagList(string $tag): string
    {
        return $this->namespace . ':tag:' . $tag;
    }

    private function unserialize(string $raw): mixed
    {
        return \unserialize($raw);
    }
}
