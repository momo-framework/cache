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

use Memcached;
use Momo\Cache\Contracts\CacheStoreInterface;
use Momo\Cache\Exceptions\CacheException;
use Redis;

/**
 * Builds a shared {@see CacheStoreInterface} (Redis or Memcached) from config.
 *
 * UNVERIFIED: constructs ext-redis / ext-memcached clients, absent where this
 * shipped, so it is excluded from static analysis. The stores it returns
 * ({@see RedisStore}, {@see MemcachedStore}) are fully unit-tested via fakes;
 * only the live connection wiring here is verified by CI integration jobs.
 *
 * @param array<string, mixed> $config
 */
final class StoreFactory
{
    /**
     * @param non-empty-string     $driver
     * @param array<array-key, mixed> $config
     */
    public static function make(string $driver, array $config): CacheStoreInterface
    {
        return match ($driver) {
            'redis'     => self::redis($config),
            'memcached' => self::memcached($config),
            default     => throw new CacheException("Unknown cache store driver: {$driver}"),
        };
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private static function redis(array $config): CacheStoreInterface
    {
        $redis = new Redis();
        $redis->connect((string) ($config['host'] ?? '127.0.0.1'), (int) ($config['port'] ?? 6379));

        $password = (string) ($config['password'] ?? '');
        if ($password !== '') {
            $redis->auth($password);
        }

        if (isset($config['database'])) {
            $redis->select((int) $config['database']);
        }

        $prefix = (string) ($config['prefix'] ?? 'momo');

        return new RedisStore(new PhpRedisClient($redis), $prefix !== '' ? $prefix : 'momo');
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private static function memcached(array $config): CacheStoreInterface
    {
        $memcached = new Memcached();

        $servers = $config['servers'] ?? [['host' => '127.0.0.1', 'port' => 11211]];
        if (is_array($servers)) {
            foreach ($servers as $server) {
                if (is_array($server)) {
                    $memcached->addServer((string) ($server['host'] ?? '127.0.0.1'), (int) ($server['port'] ?? 11211));
                }
            }
        }

        $prefix = (string) ($config['prefix'] ?? 'momo');

        return new MemcachedStore(new PhpMemcachedClient($memcached), $prefix !== '' ? $prefix : 'momo');
    }
}
