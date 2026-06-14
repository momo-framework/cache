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
use Momo\Cache\Store\MemcachedStore;
use Momo\Cache\Tests\Fixtures\FakeMemcachedClient;
use PHPUnit\Framework\TestCase;

final class MemcachedStoreTest extends TestCase
{
    private FakeMemcachedClient $client;

    private MemcachedStore $store;

    protected function setUp(): void
    {
        $this->client = new FakeMemcachedClient();
        $this->store  = new MemcachedStore($this->client);
    }

    public function testMissReturnsNull(): void
    {
        self::assertNull($this->store->get('absent'));
    }

    public function testPutGetRoundtrip(): void
    {
        $this->store->put('k', ['a' => 1], null, []);

        $entry = $this->store->get('k');

        self::assertNotNull($entry);
        self::assertSame(['a' => 1], $entry->value);
    }

    public function testForget(): void
    {
        $this->store->put('k', 1, null, []);

        self::assertTrue($this->store->forget('k'));
        self::assertNull($this->store->get('k'));
    }

    public function testFlushTagRemovesTaggedEntriesAndCounts(): void
    {
        $this->store->put('a', 1, null, ['orders']);
        $this->store->put('b', 2, null, ['orders']);
        $this->store->put('c', 3, null, ['users']);

        self::assertSame(2, $this->store->flushTag('orders'));
        self::assertNull($this->store->get('a'));

        $entry = $this->store->get('c');
        self::assertNotNull($entry);
        self::assertSame(3, $entry->value);
    }

    public function testFlushClearsEverything(): void
    {
        $this->store->put('a', 1, null, []);
        $this->store->flush();

        self::assertNull($this->store->get('a'));
    }

    public function testWorksBehindCacheFacade(): void
    {
        $cache = new Cache($this->store, new PhpXxHashKeyHasher());

        $cache->set('flag', true, null, ['flags']);
        self::assertTrue($cache->get('flag'));

        self::assertSame(1, $cache->invalidateTag('flags'));
        self::assertNull($cache->get('flag'));
    }
}
