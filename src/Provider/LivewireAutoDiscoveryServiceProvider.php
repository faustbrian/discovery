<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Discovery\Provider;

use Cline\Discovery\Support\LivewireComponentDiscoveryInterface;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

use function config;
use function file_exists;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class LivewireAutoDiscoveryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $map = self::loadCachedComponents() ?? $this->discoverIfLocal();

        foreach ($map as $alias => $component) {
            Livewire::component($alias, $component);
        }
    }

    /**
     * @return null|array<string, class-string>
     */
    private static function loadCachedComponents(): ?array
    {
        $path = (string) config('discovery.paths.livewire_components');

        if (file_exists($path)) {
            /** @var array<string, class-string> $map */
            $map = require $path;

            return $map;
        }

        return null;
    }

    /**
     * @return array<string, class-string>
     */
    private function discoverIfLocal(): array
    {
        if (!$this->app->isLocal()) {
            return [];
        }

        return $this->app->make(LivewireComponentDiscoveryInterface::class)->discover();
    }
}
