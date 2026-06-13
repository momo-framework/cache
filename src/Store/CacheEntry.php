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
 * A stored cache value together with its absolute expiry timestamp.
 */
final readonly class CacheEntry
{
    /**
     * @param int<0, max>|null $expiresAt absolute Unix timestamp; null = never expires
     */
    public function __construct(
        public mixed $value,
        public ?int $expiresAt = null,
    ) {
    }

    /**
     * Whether this entry is no longer valid at the given moment.
     *
     * @param int<0, max> $now
     */
    public function isExpired(int $now): bool
    {
        return $this->expiresAt !== null && $this->expiresAt <= $now;
    }
}
