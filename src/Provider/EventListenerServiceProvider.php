<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Discovery\Provider;

use Cline\Discovery\Support\Discovery\Event\EventListenerDiscovery;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

use function config;
use function file_exists;
use function str_contains;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class EventListenerServiceProvider extends ServiceProvider
{
    /**
     * Create a new service provider instance.
     *
     * @param Application $app
     */
    public function __construct(
        $app,
        private readonly Dispatcher $dispatcher,
    ) {
        parent::__construct($app);
    }

    public function boot(): void
    {
        $map = self::loadCachedEventListeners() ?? $this->discoverIfLocal();

        foreach ($map as $event => $listeners) {
            foreach ($listeners as $listener) {
                if (str_contains($listener, '@')) {
                    // Method listener format: Class@method
                    $this->dispatcher->listen($event, $listener);
                } else {
                    // Class listener format: Class
                    $this->dispatcher->listen($event, $listener);
                }
            }
        }
    }

    /**
     * @return null|array<class-string, list<string>>
     */
    private static function loadCachedEventListeners(): ?array
    {
        $path = (string) config('discovery.paths.event_handlers');

        if (file_exists($path)) {
            /** @var array<class-string, list<string>> $map */
            $map = require $path;

            return $map;
        }

        return null;
    }

    /**
     * @return array<class-string, list<string>>
     */
    private function discoverIfLocal(): array
    {
        if (!$this->app->isLocal()) {
            return [];
        }

        return EventListenerDiscovery::discover();
    }
}
