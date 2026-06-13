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

namespace Momo\Cache\Tests\Fixtures;

use Momo\Cache\Attributes\Cached;

/**
 * A query handler whose result should be cached for 60s under the `reports` tag.
 */
#[Cached(ttl: 60, tags: ['reports'])]
final class CachedReportHandler
{
}
