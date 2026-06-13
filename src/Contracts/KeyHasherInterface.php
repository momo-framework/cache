<?php

/**
 * Part of Momo Framework.
 *
 * © Momo Framework
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Unauthorized copying, modification, or distribution of this file,
 * via any medium, is strictly prohibited without prior written permission
 * from the copyright holder.
 *
 * @author    Vahe Sargsyan <w33bvGL>
 * @copyright Momo Framework
 * @license   AGPL-3.0-or-later <https://www.gnu.org/licenses/agpl-3.0.html>
 * @link      https://github.com/momo-framework
 */

declare(strict_types=1);

namespace Momo\Cache\Contracts;

/**
 * Normalises an arbitrary cache key into a short, fixed-width digest.
 *
 * On the hot path this runs on every cache read/write; the PHP fallback uses
 * xxh3 from ext-hash, and a native `momo_cache` accelerator may replace it via
 * {@see \Momo\Cache\Hashing\KeyHasherFactory}.
 */
interface KeyHasherInterface
{
    /**
     * @param non-empty-string $key
     * @return non-empty-string
     */
    public function hash(string $key): string;
}
