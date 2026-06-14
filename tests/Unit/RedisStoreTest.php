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
use Momo\Cache\Store\RedisStore;
use Momo\Cache\Tests\Fixtures\FakeRedisClient;
use PHPUnit\Framework\TestCase;

final class RedisStoreTest extends TestCase
{
    private FakeRedisClient $client;

    private RedisStore $store;

    protected function setUp(): void
    {
        $this->client = new FakeRedisClient();
        $this->store  = new RedisStore($this->client);
    }

    public function testMissReturnsNull(): void
    {
        self::assertNull($this->store->get('absent'));
    }

    public function testPutGetRoundtripPreservesStructuredValue(): void
    {
        $this->store->put('k', ['name' => 'Vahe', 'roles' => ['admin']], null, []);

        $entry = $this->store->get('k');

        self::assertNotNull($entry);
        self::assertSame(['name' => 'Vahe', 'roles' => ['admin']], $entry->value);
    }

    public function testForget(): void
    {
        $this->store->put('k', 1, null, []);

        self::assertTrue($this->store->forget('k'));
        self::assertFalse($this->store->forget('k'));
        self::assertNull($this->store->get('k'));
    }

    public function testFlushTagRemovesTaggedEntriesAndCounts(): void
    {
        $this->store->put('a', 1, null, ['orders']);
        $this->store->put('b', 2, null, ['orders']);
        $this->store->put('c', 3, null, ['users']);

        self::assertSame(2, $this->store->flushTag('orders'));
        self::assertNull($this->store->get('a'));
        self::assertNull($this->store->get('b'));

        $entry = $this->store->get('c');
        self::assertNotNull($entry);
        self::assertSame(3, $entry->value);
    }

    public function testFlushClearsTheNamespace(): void
    {
        $this->store->put('a', 1, null, ['t']);
        $this->store->put('b', 2, null, []);

        $this->store->flush();

        self::assertNull($this->store->get('a'));
        self::assertNull($this->store->get('b'));
    }

    public function testWorksBehindCacheFacade(): void
    {
        $cache = new Cache($this->store, new PhpXxHashKeyHasher());

        $calls   = 0;
        $compute = function () use (&$calls): string {
            $calls++;

            return 'value';
        };

        $cache->remember('report', 60, $compute, ['reports']);
        $cache->remember('report', 60, $compute, ['reports']);
        $afterWarm = $calls;

        $cache->invalidateTag('reports');
        $cache->remember('report', 60, $compute, ['reports']);
        $afterInvalidate = $calls;

        self::assertSame(1, $afterWarm);
        self::assertSame(2, $afterInvalidate);
    }
}
