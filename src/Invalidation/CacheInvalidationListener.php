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

use Momo\Cache\Contracts\CacheInterface;
use Momo\Cache\Events\CacheInvalidated;
use Momo\Events\Contracts\DomainEventInterface;
use Momo\Events\Contracts\EventBusInterface;
use Momo\Events\Contracts\EventListenerInterface;

/**
 * Reactively flushes cache tags when a mapped domain event is published.
 * Subscribe it (via the service provider) to each event class registered in the
 * {@see InvalidationMap}.
 */
final class CacheInvalidationListener implements EventListenerInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly InvalidationMap $map,
        private readonly EventBusInterface $events,
    ) {
    }

    public function handle(DomainEventInterface $event): void
    {
        $tags = $this->map->tagsFor($event);

        if ($tags === []) {
            return;
        }

        $removed = $this->cache->invalidateTags($tags);

        $this->events->publish(new CacheInvalidated($event::class, $tags, $removed));
    }
}
