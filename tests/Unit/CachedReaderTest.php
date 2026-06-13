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
use Momo\Cache\Tests\Fixtures\CachedReportHandler;
use Momo\Cache\Tests\Fixtures\UncachedHandler;
use PHPUnit\Framework\TestCase;

final class CachedReaderTest extends TestCase
{
    public function testReadsAttributeFromAnnotatedClass(): void
    {
        $cached = (new CachedReader())->forClass(CachedReportHandler::class);

        self::assertNotNull($cached);
        self::assertSame(60, $cached->ttl);
        self::assertSame(['reports'], $cached->tags);
    }

    public function testReturnsNullForUnannotatedClass(): void
    {
        self::assertNull((new CachedReader())->forClass(UncachedHandler::class));
    }

    public function testMemoisesLookup(): void
    {
        $reader = new CachedReader();

        self::assertSame(
            $reader->forClass(CachedReportHandler::class),
            $reader->forClass(CachedReportHandler::class),
        );
    }
}
