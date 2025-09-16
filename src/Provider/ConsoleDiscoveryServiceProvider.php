<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Discovery\Provider;

use Cline\Discovery\Support\Discovery\Console\ConsoleCommandDiscovery;
use Illuminate\Support\ServiceProvider;
use Override;

use function config;
use function file_exists;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class ConsoleDiscoveryServiceProvider extends ServiceProvider
{
    #[Override()]
    public function register(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $commands = self::loadCachedCommands() ?? $this->discoverIfLocal();

        if ($commands !== []) {
            $this->commands($commands);
        }
    }

    /**
     * @return null|list<class-string>
     */
    private static function loadCachedCommands(): ?array
    {
        $path = (string) config('discovery.paths.console_commands');

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

        return ConsoleCommandDiscovery::discover();
    }
}
