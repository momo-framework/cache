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

namespace Momo\Cache\Hashing;

use Momo\Cache\Contracts\KeyHasherInterface;

/**
 * Pure-PHP key hasher using xxh3 from ext-hash — fast and allocation-light,
 * the default fallback when the native `momo_cache` accelerator is absent.
 */
final class PhpXxHashKeyHasher implements KeyHasherInterface
{
    public function hash(string $key): string
    {
        return \hash('xxh3', $key);
    }
}
