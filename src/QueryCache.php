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

namespace Momo\Cache;

use Momo\Cache\Attributes\CachedReader;
use Momo\Cache\Contracts\CacheInterface;

/**
 * Applies the {@see \Momo\Cache\Attributes\Cached} attribute to a query handler:
 * if the handler is annotated, its result is cached under a key derived from the
 * query's class and value; otherwise the computation runs uncached. A query bus
 * decorator calls this around handler dispatch.
 */
final class QueryCache
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly CachedReader $reader,
    ) {
    }

    /**
     * @template T
     * @param callable(): T $compute the handler invocation
     * @return T
     */
    public function remember(object $handler, object $query, callable $compute): mixed
    {
        $cached = $this->reader->forClass($handler::class);

        if ($cached === null) {
            return $compute();
        }

        return $this->cache->remember($this->keyFor($handler, $query), $cached->ttl, $compute, $cached->tags);
    }

    /**
     * @return non-empty-string
     */
    private function keyFor(object $handler, object $query): string
    {
        return $handler::class . ':' . $query::class . ':' . \md5(\serialize($query));
    }
}
