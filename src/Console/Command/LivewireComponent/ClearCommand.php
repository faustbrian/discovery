<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Discovery\Console\Command\LivewireComponent;

use Cline\Discovery\Attribute\Console\AsConsoleCommand;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

use function config;

/**
 * @author Brian Faust <brian@cline.sh>
 */
#[AsConsoleCommand('livewire-components:clear')]
final class ClearCommand extends Command
{
    protected $signature = 'livewire-components:clear';

    protected $description = 'Remove cached Livewire component map from bootstrap/cache/livewire-components.php.';

    public function handle(Filesystem $files): int
    {
        $path = (string) config('discovery.paths.livewire_components');

        if ($files->exists($path)) {
            $files->delete($path);
            $this->components->info('Livewire components cache cleared.');
        } else {
            $this->components->info('No Livewire components cache to clear.');
        }

        return self::SUCCESS;
    }
}
