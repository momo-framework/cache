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
 * Selects the key hasher at runtime: the native `momo_cache` accelerator when
 * loaded with a matching ABI, otherwise {@see PhpXxHashKeyHasher}. Any mismatch
 * degrades silently to the PHP implementation.
 */
final class KeyHasherFactory
{
    public const int SUPPORTED_ABI = 1;

    private const string EXTENSION = 'momo_cache';

    /** Native class registered by the extension; resolved as a plain string for analysis. */
    private static string $nativeClass = 'Momo\\Cache\\Native\\XxHasher';

    public static function create(): KeyHasherInterface
    {
        if (self::nativeIsUsable()) {
            $instance = self::instantiateNative();

            if ($instance instanceof KeyHasherInterface) {
                return $instance;
            }
        }

        return new PhpXxHashKeyHasher();
    }

    public static function nativeIsUsable(): bool
    {
        if (! \extension_loaded(self::EXTENSION)) {
            return false;
        }

        return \defined('MOMO_CACHE_EXT_VERSION') && \constant('MOMO_CACHE_EXT_VERSION') === self::SUPPORTED_ABI;
    }

    private static function instantiateNative(): object
    {
        $class = self::$nativeClass;

        return new $class();
    }
}
