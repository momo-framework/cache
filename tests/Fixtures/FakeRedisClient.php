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

namespace Momo\Cache\Tests\Fixtures;

use Momo\Cache\Store\RedisClientInterface;

/**
 * In-memory stand-in for Redis used to verify {@see \Momo\Cache\Store\RedisStore}
 * logic (keys, sets, deletion) without a server.
 */
final class FakeRedisClient implements RedisClientInterface
{
    /** @var array<non-empty-string, string> */
    private array $kv = [];

    /** @var array<non-empty-string, array<non-empty-string, true>> */
    private array $sets = [];

    public function get(string $key): ?string
    {
        return $this->kv[$key] ?? null;
    }

    public function set(string $key, string $value, ?int $ttl): void
    {
        $this->kv[$key] = $value;
    }

    public function del(string $key): bool
    {
        if (isset($this->kv[$key])) {
            unset($this->kv[$key]);

            return true;
        }

        if (isset($this->sets[$key])) {
            unset($this->sets[$key]);

            return true;
        }

        return false;
    }

    public function sadd(string $set, string $member): void
    {
        $this->sets[$set][$member] = true;
    }

    public function smembers(string $set): array
    {
        return \array_keys($this->sets[$set] ?? []);
    }

    public function keys(string $pattern): array
    {
        $matched = [];

        foreach ([...\array_keys($this->kv), ...\array_keys($this->sets)] as $key) {
            if (\fnmatch($pattern, $key)) {
                $matched[] = $key;
            }
        }

        return $matched;
    }
}
