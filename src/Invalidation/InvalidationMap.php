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

namespace Momo\Cache\Invalidation;

use Momo\Events\Contracts\DomainEventInterface;

/**
 * Maps domain-event classes to the cache tags they invalidate. Drives reactive
 * cache eviction: when a mapped event fires, its tags are flushed.
 */
final class InvalidationMap
{
    /** @var array<class-string<DomainEventInterface>, list<non-empty-string>> */
    private array $map = [];

    /**
     * @param class-string<DomainEventInterface> $eventClass
     * @param list<non-empty-string>             $tags
     */
    public function register(string $eventClass, array $tags): void
    {
        foreach ($tags as $tag) {
            $this->map[$eventClass][] = $tag;
        }
    }

    /**
     * Tags to flush for a fired event, de-duplicated.
     *
     * @return list<non-empty-string>
     */
    public function tagsFor(DomainEventInterface $event): array
    {
        return \array_values(\array_unique($this->map[$event::class] ?? []));
    }
}
