<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Discovery\Provider;

use Cline\Discovery\Support\Discovery\Auth\PolicyDiscovery;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

use function config;
use function file_exists;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class PolicyServiceProvider extends ServiceProvider
{
    /**
     * Create a new service provider instance.
     *
     * @param Application $app
     */
    public function __construct(
        $app,
        private readonly Gate $gate,
    ) {
        parent::__construct($app);
    }

    public function boot(): void
    {
        $map = self::loadCachedPolicies() ?? $this->discoverIfLocal();

        foreach ($map as $model => $policy) {
            $this->gate->policy($model, $policy);
        }
    }

    /**
     * @return null|array<class-string, class-string>
     */
    private static function loadCachedPolicies(): ?array
    {
        $path = (string) config('discovery.paths.policies');

        if (file_exists($path)) {
            /** @var array<class-string, class-string> $map */
            $map = require $path;

            return $map;
        }

        return null;
    }

    /**
     * @return array<class-string, class-string>
     */
    private function discoverIfLocal(): array
    {
        if (!$this->app->isLocal()) {
            return [];
        }

        return PolicyDiscovery::discover();
    }
}
