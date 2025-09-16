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
 * Attach to Infrastructure persistence models to auto-generate their corresponding
 * ReadModel DTOs and toReadModel() mapping methods. Keeps the dependency direction
 * correct by referencing the ReadModel from Infrastructure, never the reverse.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class UseReadModel
{
    /**
     * @param null|class-string     $readModel      ReadModel FQCN (auto-inferred if null)
     * @param array<string>         $excludeFields  Fields to exclude from generation
     * @param array<string, string> $customMappings Field name to type mappings
     * @param array<string, string> $fieldMappings  Source field to target field mappings
     */
    public function __construct(
        public ?string $readModel = null,
        public array $excludeFields = [],
        public array $customMappings = [],
        public array $fieldMappings = [],
    ) {}
}
