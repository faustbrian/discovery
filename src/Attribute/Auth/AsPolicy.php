<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Discovery\Attribute\Auth;

use Attribute;

/**
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsPolicy
{
    /**
     * @param class-string $entity Domain Entity FQCN
     */
    public function __construct(
        public string $entity,
    ) {}
}
