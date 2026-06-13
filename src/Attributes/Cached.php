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

namespace Momo\Cache\Attributes;

use Attribute;

/**
 * Marks a query handler whose result should be cached. Read by
 * {@see \Momo\Cache\QueryCache} to wrap handler execution in
 * {@see \Momo\Cache\Contracts\CacheInterface::remember()}.
 *
 * The declared tags let domain events invalidate the cached result reactively
 * via {@see \Momo\Cache\Invalidation\CacheInvalidationListener}.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Cached
{
    /**
     * @param int<1, max>|null       $ttl  seconds; null = no expiry
     * @param list<non-empty-string> $tags invalidation tags
     */
    public function __construct(
        public ?int $ttl = null,
        public array $tags = [],
    ) {
    }
}
