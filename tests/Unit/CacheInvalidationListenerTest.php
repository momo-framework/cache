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

use Momo\Cache\Cache;
use Momo\Cache\Events\CacheInvalidated;
use Momo\Cache\Hashing\PhpXxHashKeyHasher;
use Momo\Cache\Invalidation\CacheInvalidationListener;
use Momo\Cache\Invalidation\InvalidationMap;
use Momo\Cache\Store\ArrayStore;
use Momo\Cache\Support\MutableClock;
use Momo\Cache\Tests\Fixtures\OrderShippedEvent;
use Momo\Cache\Tests\Fixtures\SpyEventBus;
use PHPUnit\Framework\TestCase;

final class CacheInvalidationListenerTest extends TestCase
{
    private Cache $cache;

    private InvalidationMap $map;

    private SpyEventBus $events;

    private CacheInvalidationListener $listener;

    protected function setUp(): void
    {
        $this->cache    = new Cache(new ArrayStore(new MutableClock(0)), new PhpXxHashKeyHasher());
        $this->map      = new InvalidationMap();
        $this->events   = new SpyEventBus();
        $this->listener = new CacheInvalidationListener($this->cache, $this->map, $this->events);
    }

    public function testMappedEventFlushesTagsAndEmitsEvent(): void
    {
        $this->map->register(OrderShippedEvent::class, ['orders']);
        $this->cache->set('orders:1', 'x', null, ['orders']);

        $this->listener->handle(new OrderShippedEvent('o-1'));

        self::assertFalse($this->cache->has('orders:1'));
        self::assertSame(1, $this->events->countOf(CacheInvalidated::class));
    }

    public function testUnmappedEventDoesNothing(): void
    {
        $this->cache->set('orders:1', 'x', null, ['orders']);

        $this->listener->handle(new OrderShippedEvent('o-1'));

        self::assertTrue($this->cache->has('orders:1'));
        self::assertSame(0, $this->events->countOf(CacheInvalidated::class));
    }
}
