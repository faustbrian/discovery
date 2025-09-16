<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Discovery\Console\Command\Policy;

use Cline\Discovery\Attribute\Console\AsConsoleCommand;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

use function config;

/**
 * @author Brian Faust <brian@cline.sh>
 */
#[AsConsoleCommand('policies:clear')]
final class ClearCommand extends Command
{
    protected $signature = 'policies:clear';

    protected $description = 'Remove cached policies map from bootstrap/cache/policies.php.';

    public function handle(Filesystem $files): int
    {
        $path = (string) config('discovery.paths.policies');

        if ($files->exists($path)) {
            $files->delete($path);
            $this->components->info('Policies cache cleared.');
        } else {
            $this->components->info('No policies cache to clear.');
        }

        return self::SUCCESS;
    }
}
