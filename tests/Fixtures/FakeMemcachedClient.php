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

use Momo\Cache\Store\MemcachedClientInterface;

/**
 * In-memory stand-in for Memcached used to verify
 * {@see \Momo\Cache\Store\MemcachedStore} logic without a server.
 */
final class FakeMemcachedClient implements MemcachedClientInterface
{
    /** @var array<non-empty-string, string> */
    private array $kv = [];

    public function get(string $key): ?string
    {
        return $this->kv[$key] ?? null;
    }

    public function set(string $key, string $value, ?int $ttl): void
    {
        $this->kv[$key] = $value;
    }

    public function delete(string $key): bool
    {
        if (! isset($this->kv[$key])) {
            return false;
        }

        unset($this->kv[$key]);

        return true;
    }

    public function flush(): void
    {
        $this->kv = [];
    }
}
