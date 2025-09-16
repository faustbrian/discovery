<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Discovery\Support;

/**
 * @author Brian Faust <brian@cline.sh>
 */
interface LivewireComponentDiscoveryInterface
{
    /**
     * @return array<string, class-string> alias => component class
     */
    public function discover(): array;
}
