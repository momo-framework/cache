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

namespace Momo\Cache\Events;

use Momo\Events\DomainEvent;

/**
 * Emitted after the reactive listener flushed cache tags in response to a
 * domain event.
 */
final class CacheInvalidated extends DomainEvent
{
    /**
     * @param class-string           $trigger the domain event class that caused the flush
     * @param list<non-empty-string> $tags    tags that were flushed
     * @param int<0, max>            $removed number of entries removed
     */
    public function __construct(
        public readonly string $trigger,
        public readonly array $tags,
        public readonly int $removed,
    ) {
        parent::__construct();
    }
}
