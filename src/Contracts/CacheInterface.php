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

/**
 * High-level cache facade.
 *
 * Values are heterogeneous by nature, so the value slot is the one place this
 * package uses an explicit `mixed`; the generic {@see remember()} restores type
 * safety at every call site that matters.
 */
interface CacheInterface
{
    /**
     * @param non-empty-string $key
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * @param non-empty-string       $key
     * @param int<1, max>|null       $ttl seconds; null = no expiry
     * @param list<non-empty-string> $tags
     */
    public function set(string $key, mixed $value, ?int $ttl = null, array $tags = []): void;

    /**
     * @param non-empty-string $key
     */
    public function has(string $key): bool;

    /**
     * @param non-empty-string $key
     */
    public function delete(string $key): bool;

    public function clear(): void;

    /**
     * Return the cached value for $key, or compute it with $callback, store it,
     * and return it.
     *
     * @template T
     * @param non-empty-string       $key
     * @param int<1, max>|null       $ttl
     * @param callable(): T          $callback
     * @param list<non-empty-string> $tags
     * @return T
     */
    public function remember(string $key, ?int $ttl, callable $callback, array $tags = []): mixed;

    /**
     * Invalidate every entry carrying the given tag.
     *
     * @param non-empty-string $tag
     * @return int<0, max> entries removed
     */
    public function invalidateTag(string $tag): int;

    /**
     * @param list<non-empty-string> $tags
     * @return int<0, max> total entries removed
     */
    public function invalidateTags(array $tags): int;
}
