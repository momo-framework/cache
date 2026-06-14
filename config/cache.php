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

return [
    /*
     | Active store driver: 'array' (in-process, default), 'redis' or
     | 'memcached'. Long-running multi-worker Swoole deployments need a shared
     | store ('redis' recommended) so cache and tag invalidation are visible
     | across workers; 'array' is per-process only.
     */
    'store' => 'array',

    /*
     | Connection settings per shared driver. Used by StoreFactory when 'store'
     | is 'redis' or 'memcached'.
     */
    'stores' => [
        'redis' => [
            'host'     => '127.0.0.1',
            'port'     => 6379,
            'database' => 0,
            'password' => '',
            'prefix'   => 'momo',
        ],
        'memcached' => [
            'servers' => [
                ['host' => '127.0.0.1', 'port' => 11211],
            ],
            'prefix' => 'momo',
        ],
    ],

    /*
     | Default TTL in seconds applied when a caller passes none. null = entries
     | never expire unless explicitly evicted or invalidated by tag.
     */
    'default_ttl' => 3600,

    /*
     | Reactive invalidation map: domain-event class => cache tags to flush when
     | that event is published. The service provider subscribes the invalidation
     | listener to each event listed here.
     |
     |   \App\Orders\Events\OrderShipped::class => ['orders', 'dashboard'],
     */
    'invalidation' => [],
];
