<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Discovery\Console\Command\ConsoleCommand;

use Cline\Discovery\Attribute\Console\AsConsoleCommand;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

use function config;

/**
 * @author Brian Faust <brian@cline.sh>
 */
#[AsConsoleCommand('console-commands:clear')]
final class ClearCommand extends Command
{
    protected $signature = 'console-commands:clear';

    protected $description = 'Remove cached console command list from bootstrap/cache/console-commands.php.';

    public function handle(Filesystem $files): int
    {
        $path = (string) config('discovery.paths.console_commands');

        if ($files->exists($path)) {
            $files->delete($path);
            $this->components->info('Console commands cache cleared.');
        } else {
            $this->components->info('No console commands cache to clear.');
        }

        return self::SUCCESS;
    }
}
