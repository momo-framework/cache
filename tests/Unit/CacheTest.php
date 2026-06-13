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
use Momo\Cache\Hashing\PhpXxHashKeyHasher;
use Momo\Cache\Store\ArrayStore;
use Momo\Cache\Support\MutableClock;
use PHPUnit\Framework\TestCase;

final class CacheTest extends TestCase
{
    private MutableClock $clock;

    private Cache $cache;

    protected function setUp(): void
    {
        $this->clock = new MutableClock(1_000);
        $this->cache = new Cache(new ArrayStore($this->clock), new PhpXxHashKeyHasher());
    }

    public function testSetAndGet(): void
    {
        $this->cache->set('user:1', ['name' => 'Vahe']);

        self::assertSame(['name' => 'Vahe'], $this->cache->get('user:1'));
    }

    public function testGetReturnsDefaultOnMiss(): void
    {
        self::assertSame('fallback', $this->cache->get('absent', 'fallback'));
    }

    public function testHasAndDelete(): void
    {
        $this->cache->set('k', 1);

        self::assertTrue($this->cache->has('k'));
        self::assertTrue($this->cache->delete('k'));
        self::assertFalse($this->cache->has('k'));
    }

    public function testClear(): void
    {
        $this->cache->set('a', 1);
        $this->cache->set('b', 2);

        $this->cache->clear();

        self::assertFalse($this->cache->has('a'));
        self::assertFalse($this->cache->has('b'));
    }

    public function testRememberComputesOnceThenServesFromCache(): void
    {
        $calls = 0;
        $compute = function () use (&$calls): string {
            $calls++;

            return 'computed';
        };

        $first  = $this->cache->remember('k', 60, $compute);
        $second = $this->cache->remember('k', 60, $compute);

        self::assertSame('computed', $first);
        self::assertSame('computed', $second);
        self::assertSame(1, $calls);
    }

    public function testRememberRecomputesAfterTtlExpiry(): void
    {
        $calls = 0;
        $compute = function () use (&$calls): int {
            $calls++;

            return $calls;
        };

        self::assertSame(1, $this->cache->remember('k', 60, $compute));

        $this->clock->advance(61);

        self::assertSame(2, $this->cache->remember('k', 60, $compute));
    }

    public function testInvalidateTagEvictsTaggedEntries(): void
    {
        $this->cache->set('orders:1', 'a', null, ['orders']);
        $this->cache->set('orders:2', 'b', null, ['orders']);
        $this->cache->set('users:1', 'c', null, ['users']);

        self::assertSame(2, $this->cache->invalidateTag('orders'));

        self::assertFalse($this->cache->has('orders:1'));
        self::assertTrue($this->cache->has('users:1'));
    }

    public function testInvalidateTagsAcrossSeveralTags(): void
    {
        $this->cache->set('a', 1, null, ['x']);
        $this->cache->set('b', 2, null, ['y']);

        self::assertSame(2, $this->cache->invalidateTags(['x', 'y']));
    }
}
