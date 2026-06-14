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

/**
 * ext-memcached adapter for {@see MemcachedClientInterface}.
 *
 * UNVERIFIED: requires ext-memcached and a live server, neither present where
 * this shipped, so it is excluded from static analysis and not exercised by the
 * unit suite — {@see MemcachedStore} is fully tested against an in-memory fake
 * instead. Verify with a Memcached integration job in CI.
 */
final class PhpMemcachedClient implements MemcachedClientInterface
{
    public function __construct(
        private readonly Memcached $memcached,
    ) {
    }

    public function get(string $key): ?string
    {
        $value = $this->memcached->get($key);

        if ($value === false && $this->memcached->getResultCode() === Memcached::RES_NOTFOUND) {
            return null;
        }

        return (string) $value;
    }

    public function set(string $key, string $value, ?int $ttl): void
    {
        $this->memcached->set($key, $value, $ttl ?? 0);
    }

    public function delete(string $key): bool
    {
        return $this->memcached->delete($key);
    }

    public function flush(): void
    {
        $this->memcached->flush();
    }
}
