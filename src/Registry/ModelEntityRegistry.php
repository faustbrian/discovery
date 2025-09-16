<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Discovery\Registry;

use Cline\Discovery\Support\Discovery\Persistence\DomainEntityDiscovery;

use function config;
use function file_exists;

/**
 * Lightweight registry for model => domain entity mappings.
 * Populates from cache in production, with optional local discovery fallback.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ModelEntityRegistry
{
    public function __construct(
        /** @var array<class-string, class-string> */
        private array $map = [],
    ) {}

    /**
     * Load cached map from bootstrap/cache, if present.
     *
     * @return array<class-string, class-string>
     */
    public static function loadCached(): array
    {
        $path = (string) config('discovery.paths.domain_entities');

        if (file_exists($path)) {
            /** @var array<class-string, class-string> $map */
            $map = require $path;

            return $map;
        }

        return [];
    }

    /**
     * Discover map via attributes; intended for local/dev fallback.
     *
     * @return array<class-string, class-string>
     */
    public static function discover(): array
    {
        return DomainEntityDiscovery::discover();
    }

    /**
     * @return array<class-string, class-string>
     */
    public function all(): array
    {
        return $this->map;
    }

    /**
     * @return null|class-string
     */
    public function forModel(string $model): ?string
    {
        return $this->map[$model] ?? null;
    }

    /**
     * @param array<class-string, class-string> $map
     */
    public function setMap(array $map): void
    {
        $this->map = $map;
    }
}
