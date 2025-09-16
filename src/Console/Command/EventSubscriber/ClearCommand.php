<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Discovery\Console\Command\EventSubscriber;

use Cline\Discovery\Attribute\Console\AsConsoleCommand;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

use function config;

/**
 * @author Brian Faust <brian@cline.sh>
 */
#[AsConsoleCommand('event-subscribers:clear')]
final class ClearCommand extends Command
{
    protected $signature = 'event-subscribers:clear';

    protected $description = 'Remove cached event subscribers map from bootstrap/cache/event-subscribers.php.';

    public function handle(Filesystem $files): int
    {
        $path = (string) config('discovery.paths.event_subscribers');

        if ($files->exists($path)) {
            $files->delete($path);
            $this->components->info('Event subscribers cache cleared.');
        } else {
            $this->components->info('No event subscribers cache to clear.');
        }

        return self::SUCCESS;
    }
}
