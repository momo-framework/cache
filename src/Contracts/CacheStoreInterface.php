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

namespace Momo\Cache\Contracts;

use Momo\Cache\Store\CacheEntry;

/**
 * Low-level key/value backend with TTL and tag support. The high-level
 * {@see CacheInterface} is layered on top; swap this for Redis, a Swoole Table,
 * etc. The default is {@see \Momo\Cache\Store\ArrayStore}.
 *
 * Implementations are responsible for treating an expired entry as a miss.
 */
interface CacheStoreInterface
{
    /**
     * Fetch a live entry, or null when missing or expired.
     *
     * @param non-empty-string $key
     */
    public function get(string $key): ?CacheEntry;

    /**
     * Store a value under a key with an optional relative TTL and tag set.
     *
     * @param non-empty-string        $key
     * @param int<1, max>|null        $ttlSeconds null = no expiry
     * @param list<non-empty-string>  $tags
     */
    public function put(string $key, mixed $value, ?int $ttlSeconds, array $tags): void;

    /**
     * Remove one key. Returns false when it was absent.
     *
     * @param non-empty-string $key
     */
    public function forget(string $key): bool;

    /**
     * Remove every entry.
     */
    public function flush(): void;

    /**
     * Remove every entry associated with a tag.
     *
     * @param non-empty-string $tag
     * @return int<0, max> number of entries removed
     */
    public function flushTag(string $tag): int;
}
