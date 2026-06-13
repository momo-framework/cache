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

/**
 * Minimal Redis command surface the {@see RedisStore} depends on. Abstracting
 * it keeps the store logic testable with an in-memory fake and lets an app bind
 * either a phpredis adapter (with Swoole runtime hooks for non-blocking I/O) or
 * a native coroutine Redis client.
 */
interface RedisClientInterface
{
    /**
     * @param non-empty-string $key
     */
    public function get(string $key): ?string;

    /**
     * Store a string value with an optional relative TTL in seconds.
     *
     * @param non-empty-string $key
     * @param int<1, max>|null $ttl
     */
    public function set(string $key, string $value, ?int $ttl): void;

    /**
     * Delete a key; returns true when it existed.
     *
     * @param non-empty-string $key
     */
    public function del(string $key): bool;

    /**
     * Add a member to a set (Redis SADD).
     *
     * @param non-empty-string $set
     * @param non-empty-string $member
     */
    public function sadd(string $set, string $member): void;

    /**
     * Members of a set (Redis SMEMBERS).
     *
     * @param non-empty-string $set
     * @return list<non-empty-string>
     */
    public function smembers(string $set): array;

    /**
     * Keys matching a glob-style pattern. Adapters should implement this with
     * SCAN (non-blocking) rather than KEYS in production.
     *
     * @param non-empty-string $pattern
     * @return list<non-empty-string>
     */
    public function keys(string $pattern): array;
}
