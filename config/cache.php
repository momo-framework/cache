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
