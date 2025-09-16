<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Discovery\Support\Discovery\Auth;

use Cline\Discovery\Attribute\Auth\AsPolicy;
use ReflectionAttribute;
use ReflectionClass;
use Spatie\StructureDiscoverer\Discover;
use Throwable;

use function base_path;
use function class_exists;
use function file_exists;
use function ksort;
use function str_contains;
use function str_starts_with;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class PolicyDiscovery
{
    /**
     * @return array<class-string, class-string> model => policy
     */
    public static function discover(): array
    {
        $map = self::discoverViaComposerClassmap();

        if ($map === []) {
            $map = self::discoverViaSourceScan();
        }

        ksort($map);

        return $map;
    }

    /**
     * @return array<class-string, class-string>
     */
    private static function discoverViaComposerClassmap(): array
    {
        $map = [];
        $classmapPath = base_path('vendor/composer/autoload_classmap.php');

        if (!file_exists($classmapPath)) {
            return [];
        }

        /** @var array<class-string, string> $classmap */
        $classmap = require $classmapPath;

        foreach ($classmap as $class => $file) {
            if (!str_starts_with($class, 'Cline\\')) {
                continue;
            }

            $path = (string) $file;

            if (!str_contains($path, '/Policy')) {
                continue;
            }

            try {
                $ref = new ReflectionClass($class);
            } catch (Throwable) {
                continue;
            }

            if ($ref->isAbstract() || $ref->isInterface()) {
                continue;
            }

            foreach ($ref->getAttributes(AsPolicy::class, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                /** @var AsPolicy $instance */
                $instance = $attr->newInstance();
                $map[$instance->model] = $class;
            }
        }

        return $map;
    }

    /**
     * @return array<class-string, class-string>
     */
    private static function discoverViaSourceScan(): array
    {
        $map = [];

        try {
            if (class_exists(Discover::class)) {
                /** @var iterable<class-string> $classes */
                $classes = Discover::in(base_path('src'))
                    ->attributes(AsPolicy::class)
                    ->classes();

                foreach ($classes as $class) {
                    try {
                        $ref = new ReflectionClass($class);
                    } catch (Throwable) {
                        continue;
                    }

                    if ($ref->isAbstract() || $ref->isInterface()) {
                        continue;
                    }

                    foreach ($ref->getAttributes(AsPolicy::class, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                        /** @var AsPolicy $instance */
                        $instance = $attr->newInstance();
                        $map[$instance->model] = $class;
                    }
                }
            }
        } catch (Throwable) {
            // ignore
        }

        return $map;
    }
}
