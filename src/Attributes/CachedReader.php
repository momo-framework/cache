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

namespace Momo\Cache\Attributes;

use ReflectionClass;

/**
 * Resolves the {@see Cached} attribute declared on a class, with a per-class
 * memoised lookup so reflection runs once per handler in a long-lived worker.
 */
final class CachedReader
{
    /** @var array<class-string, Cached|null> */
    private array $cache = [];

    /**
     * @param class-string $class
     */
    public function forClass(string $class): ?Cached
    {
        if (\array_key_exists($class, $this->cache)) {
            return $this->cache[$class];
        }

        $attributes = (new ReflectionClass($class))->getAttributes(Cached::class);

        return $this->cache[$class] = $attributes === []
            ? null
            : $attributes[0]->newInstance();
    }
}
