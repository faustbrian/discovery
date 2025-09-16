<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Discovery\Attribute\Persistence;

use Attribute;

/**
 * Attach to Infrastructure persistence models to declare their corresponding
 * pure Domain Entity. Keeps the dependency direction correct by referencing
 * the Domain from Infrastructure, never the reverse.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class UseDomainEntity
{
    /**
     * @param class-string $entity Domain Entity FQCN
     */
    public function __construct(
        public string $entity,
    ) {}
}
