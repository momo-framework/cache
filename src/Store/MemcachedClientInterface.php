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
 * Minimal Memcached command surface the {@see MemcachedStore} depends on.
 * Abstracting it keeps the store logic testable with an in-memory fake.
 */
interface MemcachedClientInterface
{
    /**
     * @param non-empty-string $key
     */
    public function get(string $key): ?string;

    /**
     * Store a value with an optional relative TTL in seconds (null = no expiry).
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
    public function delete(string $key): bool;

    /**
     * Invalidate the entire Memcached instance.
     */
    public function flush(): void;
}
