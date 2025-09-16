<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Discovery\Provider;

use Cline\Discovery\Registry\ModelEntityRegistry;
use Illuminate\Support\ServiceProvider;
use Override;

/**
 * Wires a ModelEntityRegistry using cached map, with local discovery fallback.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class DomainEntityServiceProvider extends ServiceProvider
{
    #[Override()]
    public function register(): void
    {
        $this->app->scoped(ModelEntityRegistry::class, function ($app): ModelEntityRegistry {
            $cached = ModelEntityRegistry::loadCached();

            if ($cached !== []) {
                return new ModelEntityRegistry($cached);
            }

            if ($app->isLocal()) {
                return new ModelEntityRegistry(ModelEntityRegistry::discover());
            }

            return new ModelEntityRegistry();
        });
    }
}
