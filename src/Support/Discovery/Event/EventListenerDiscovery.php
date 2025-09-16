<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Discovery\Support\Discovery\Event;

use Cline\Discovery\Attribute\Event\AsEventListener;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Spatie\StructureDiscoverer\Discover;
use Throwable;

use function base_path;
use function class_exists;
use function file_exists;
use function ksort;
use function sort;
use function str_contains;
use function str_starts_with;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class EventListenerDiscovery
{
    /**
     * @return array<class-string, list<string>> event => [listeners]
     */
    public static function discover(): array
    {
        $map = self::discoverViaComposerClassmap();

        if ($map === []) {
            $map = self::discoverViaSourceScan();
        }

        ksort($map);

        foreach ($map as &$listeners) {
            sort($listeners);
        }

        unset($listeners);

        return $map;
    }

    /**
     * @return array<class-string, list<string>>
     */
    private static function discoverViaComposerClassmap(): array
    {
        $events = [];
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

            if (!str_contains($path, '/Listener')) {
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

            // Check class-level attributes
            foreach ($ref->getAttributes(AsEventListener::class, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                /** @var AsEventListener $instance */
                $instance = $attr->newInstance();
                $events[$instance->event] ??= [];
                $events[$instance->event][] = $class;
            }

            // Check method-level attributes
            foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes(AsEventListener::class, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                    /** @var AsEventListener $instance */
                    $instance = $attr->newInstance();
                    $events[$instance->event] ??= [];
                    $events[$instance->event][] = $class.'@'.$method->getName();
                }
            }
        }

        return $events;
    }

    /**
     * @return array<class-string, list<string>>
     */
    private static function discoverViaSourceScan(): array
    {
        $events = [];

        try {
            if (class_exists(Discover::class)) {
                /** @var iterable<class-string> $classes */
                $classes = Discover::in(base_path('src'))
                    ->attributes(AsEventListener::class)
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

                    // Check class-level attributes
                    foreach ($ref->getAttributes(AsEventListener::class, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                        /** @var AsEventListener $instance */
                        $instance = $attr->newInstance();
                        $events[$instance->event] ??= [];
                        $events[$instance->event][] = $class;
                    }

                    // Check method-level attributes
                    foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                        foreach ($method->getAttributes(AsEventListener::class, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                            /** @var AsEventListener $instance */
                            $instance = $attr->newInstance();
                            $events[$instance->event] ??= [];
                            $events[$instance->event][] = $class.'@'.$method->getName();
                        }
                    }
                }
            }
        } catch (Throwable) { /* ignore */
        }

        return $events;
    }
}
