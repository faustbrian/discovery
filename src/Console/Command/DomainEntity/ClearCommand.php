<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Discovery\Console\Command\DomainEntity;

use Cline\Discovery\Attribute\Console\AsConsoleCommand;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

use function config;

/**
 * Remove cached model => domain entity map.
 *
 * @author Brian Faust <brian@cline.sh>
 */
#[AsConsoleCommand('domain-entities:clear')]
final class ClearCommand extends Command
{
    protected $signature = 'domain-entities:clear';

    protected $description = 'Remove cached model/entity map from bootstrap/cache/domain-entities.php.';

    public function handle(Filesystem $files): int
    {
        $path = (string) config('discovery.paths.domain_entities');

        if ($files->exists($path)) {
            $files->delete($path);
            $this->components->info('Domain entities cache cleared.');
        } else {
            $this->components->info('No domain entities cache to clear.');
        }

        return self::SUCCESS;
    }
}
