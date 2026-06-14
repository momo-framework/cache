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

use Redis;

/**
 * phpredis adapter for {@see RedisClientInterface}.
 *
 * UNVERIFIED: requires ext-redis and a live server, neither present where this
 * shipped, so it is excluded from static analysis and not exercised by the unit
 * suite — {@see RedisStore} is fully tested against an in-memory fake instead.
 * Verify with a Redis integration job in CI.
 *
 * NON-BLOCKING: enable `Swoole\Runtime::enableCoroutine()` so phpredis socket
 * calls yield the scheduler instead of blocking the worker.
 */
final class PhpRedisClient implements RedisClientInterface
{
    public function __construct(
        private readonly Redis $redis,
    ) {
    }

    public function get(string $key): ?string
    {
        $value = $this->redis->get($key);

        return $value === false ? null : (string) $value;
    }

    public function set(string $key, string $value, ?int $ttl): void
    {
        if ($ttl === null) {
            $this->redis->set($key, $value);

            return;
        }

        $this->redis->setex($key, $ttl, $value);
    }

    public function del(string $key): bool
    {
        return (int) $this->redis->del($key) > 0;
    }

    public function sadd(string $set, string $member): void
    {
        $this->redis->sAdd($set, $member);
    }

    public function smembers(string $set): array
    {
        $members = [];

        foreach ($this->redis->sMembers($set) as $member) {
            $member = (string) $member;
            if ($member !== '') {
                $members[] = $member;
            }
        }

        return $members;
    }

    public function keys(string $pattern): array
    {
        $keys = [];

        foreach ($this->redis->keys($pattern) as $key) {
            $key = (string) $key;
            if ($key !== '') {
                $keys[] = $key;
            }
        }

        return $keys;
    }
}
