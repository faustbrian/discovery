<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Discovery\Provider;

use Cline\Discovery\Support\Discovery\Event\EventSubscriberDiscovery;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

use function config;
use function file_exists;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class EventSubscriberServiceProvider extends ServiceProvider
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
        $subscribers = self::loadCachedSubscribers() ?? $this->discoverIfLocal();

        foreach ($subscribers as $subscriber) {
            $this->dispatcher->subscribe($subscriber);
        }
    }

    /**
     * @return null|list<class-string>
     */
    private static function loadCachedSubscribers(): ?array
    {
        $path = (string) config('discovery.paths.event_subscribers');

        if (file_exists($path)) {
            /** @var list<class-string> $list */
            $list = require $path;

            return $list;
        }

        return null;
    }

    /**
     * @return list<class-string>
     */
    private function discoverIfLocal(): array
    {
        if (!$this->app->isLocal()) {
            return [];
        }

        return EventSubscriberDiscovery::discover();
    }
}
