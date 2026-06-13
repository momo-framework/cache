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

use Momo\Cache\Store\ArrayStore;
use Momo\Cache\Support\MutableClock;
use PHPUnit\Framework\TestCase;

final class ArrayStoreTest extends TestCase
{
    public function testMissReturnsNull(): void
    {
        $store = new ArrayStore(new MutableClock(0));

        self::assertNull($store->get('absent'));
    }

    public function testPutAndGet(): void
    {
        $store = new ArrayStore(new MutableClock(0));
        $store->put('k', 'value', null, []);

        self::assertSame('value', $store->get('k')?->value);
    }

    public function testEntryExpiresAfterTtl(): void
    {
        $clock = new MutableClock(1_000);
        $store = new ArrayStore($clock);
        $store->put('k', 'value', 60, []);

        self::assertNotNull($store->get('k'));

        $clock->advance(60);

        self::assertNull($store->get('k'));
    }

    public function testForget(): void
    {
        $store = new ArrayStore(new MutableClock(0));
        $store->put('k', 1, null, []);

        self::assertTrue($store->forget('k'));
        self::assertFalse($store->forget('k'));
        self::assertNull($store->get('k'));
    }

    public function testFlushClearsEverything(): void
    {
        $store = new ArrayStore(new MutableClock(0));
        $store->put('a', 1, null, []);
        $store->put('b', 2, null, []);

        $store->flush();

        self::assertNull($store->get('a'));
        self::assertNull($store->get('b'));
    }

    public function testFlushTagRemovesOnlyTaggedEntries(): void
    {
        $store = new ArrayStore(new MutableClock(0));
        $store->put('a', 1, null, ['orders']);
        $store->put('b', 2, null, ['orders', 'reports']);
        $store->put('c', 3, null, ['reports']);

        self::assertSame(2, $store->flushTag('orders'));

        self::assertNull($store->get('a'));
        self::assertNull($store->get('b'));
        self::assertSame(3, $store->get('c')?->value);
    }

    public function testFlushUnknownTagRemovesNothing(): void
    {
        $store = new ArrayStore(new MutableClock(0));

        self::assertSame(0, $store->flushTag('nope'));
    }
}
