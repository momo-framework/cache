<?php

/**
 * Part of Momo Framework.
 *
 * © Momo Framework
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author    Vahe Sargsyan <w33bvGL>
 * @copyright Momo Framework
 * @license   AGPL-3.0-or-later <https://www.gnu.org/licenses/agpl-3.0.html>
 * @link      https://github.com/momo-framework
 */

declare(strict_types=1);

namespace Momo\Cache\Tests\Unit;

use Momo\Cache\Invalidation\InvalidationMap;
use Momo\Cache\Tests\Fixtures\OrderShippedEvent;
use PHPUnit\Framework\TestCase;

final class InvalidationMapTest extends TestCase
{
    public function testUnmappedEventHasNoTags(): void
    {
        $map = new InvalidationMap();

        self::assertSame([], $map->tagsFor(new OrderShippedEvent('o-1')));
    }

    public function testRegisteredTagsAreReturned(): void
    {
        $map = new InvalidationMap();
        $map->register(OrderShippedEvent::class, ['orders', 'dashboard']);

        self::assertSame(['orders', 'dashboard'], $map->tagsFor(new OrderShippedEvent('o-1')));
    }

    public function testTagsAreDeduplicated(): void
    {
        $map = new InvalidationMap();
        $map->register(OrderShippedEvent::class, ['orders']);
        $map->register(OrderShippedEvent::class, ['orders', 'dashboard']);

        self::assertSame(['orders', 'dashboard'], $map->tagsFor(new OrderShippedEvent('o-1')));
    }
}
