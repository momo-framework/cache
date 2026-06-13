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

use Momo\Cache\Attributes\CachedReader;
use Momo\Cache\Cache;
use Momo\Cache\Hashing\PhpXxHashKeyHasher;
use Momo\Cache\QueryCache;
use Momo\Cache\Store\ArrayStore;
use Momo\Cache\Support\MutableClock;
use Momo\Cache\Tests\Fixtures\CachedReportHandler;
use Momo\Cache\Tests\Fixtures\ReportQuery;
use Momo\Cache\Tests\Fixtures\UncachedHandler;
use PHPUnit\Framework\TestCase;

final class QueryCacheTest extends TestCase
{
    private Cache $cache;

    private QueryCache $queryCache;

    protected function setUp(): void
    {
        $this->cache      = new Cache(new ArrayStore(new MutableClock(0)), new PhpXxHashKeyHasher());
        $this->queryCache = new QueryCache($this->cache, new CachedReader());
    }

    public function testCachedHandlerComputesOncePerQuery(): void
    {
        $calls   = 0;
        $compute = function () use (&$calls): string {
            $calls++;

            return 'report';
        };

        $handler = new CachedReportHandler();
        $query   = new ReportQuery('2026-06');

        self::assertSame('report', $this->queryCache->remember($handler, $query, $compute));
        self::assertSame('report', $this->queryCache->remember($handler, $query, $compute));
        self::assertSame(1, $calls);
    }

    public function testDifferentQueryValueRecomputes(): void
    {
        $calls   = 0;
        $compute = function () use (&$calls): int {
            $calls++;

            return $calls;
        };

        $handler = new CachedReportHandler();

        $this->queryCache->remember($handler, new ReportQuery('2026-06'), $compute);
        $this->queryCache->remember($handler, new ReportQuery('2026-07'), $compute);

        self::assertSame(2, $calls);
    }

    public function testUncachedHandlerAlwaysComputes(): void
    {
        $calls   = 0;
        $compute = function () use (&$calls): int {
            $calls++;

            return $calls;
        };

        $handler = new UncachedHandler();
        $query   = new ReportQuery('2026-06');

        $this->queryCache->remember($handler, $query, $compute);
        $this->queryCache->remember($handler, $query, $compute);

        self::assertSame(2, $calls);
    }

    public function testTagInvalidationForcesRecompute(): void
    {
        $calls   = 0;
        $compute = function () use (&$calls): int {
            $calls++;

            return $calls;
        };

        $handler = new CachedReportHandler();
        $query   = new ReportQuery('2026-06');

        $this->queryCache->remember($handler, $query, $compute);
        $this->cache->invalidateTag('reports');
        $this->queryCache->remember($handler, $query, $compute);

        self::assertSame(2, $calls);
    }
}
