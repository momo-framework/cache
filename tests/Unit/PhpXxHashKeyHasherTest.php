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

use Momo\Cache\Hashing\PhpXxHashKeyHasher;
use PHPUnit\Framework\TestCase;

final class PhpXxHashKeyHasherTest extends TestCase
{
    public function testDeterministic(): void
    {
        $hasher = new PhpXxHashKeyHasher();

        self::assertSame($hasher->hash('order:42'), $hasher->hash('order:42'));
    }

    public function testDistinctKeysProduceDistinctDigests(): void
    {
        $hasher = new PhpXxHashKeyHasher();

        self::assertNotSame($hasher->hash('order:42'), $hasher->hash('order:43'));
    }

    public function testDigestIsNonEmpty(): void
    {
        self::assertNotSame('', (new PhpXxHashKeyHasher())->hash('anything'));
    }
}
