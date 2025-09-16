<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Discovery\Console\Command\Handler;

use Cline\Discovery\Attribute\Console\AsConsoleCommand;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

use function config;

/**
 * @author Brian Faust <brian@cline.sh>
 */
#[AsConsoleCommand('handlers:clear')]
final class ClearCommand extends Command
{
    protected $signature = 'handlers:clear';

    protected $description = 'Remove cached handler maps from bootstrap/cache.';

    public function handle(Filesystem $files): int
    {
        $paths = [
            (string) config('discovery.paths.command_handlers'),
            (string) config('discovery.paths.query_handlers'),
            (string) config('discovery.paths.event_handlers'),
        ];

        $removed = false;

        foreach ($paths as $path) {
            if (!$files->exists($path)) {
                continue;
            }

            $files->delete($path);
            $removed = true;
        }

        if ($removed) {
            $this->components->info('Handlers cache cleared.');
        } else {
            $this->components->info('No handlers cache to clear.');
        }

        return self::SUCCESS;
    }
}
