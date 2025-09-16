<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Discovery\Support\Discovery\Console;

use Cline\Discovery\Attribute\Console\AsConsoleCommand;
use Illuminate\Console\Command;
use ReflectionAttribute;
use ReflectionClass;
use Spatie\StructureDiscoverer\Discover;
use Throwable;

use function array_unique;
use function array_values;
use function base_path;
use function class_exists;
use function file_exists;
use function sort;
use function str_contains;
use function str_starts_with;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class ConsoleCommandDiscovery
{
    /**
     * @return list<class-string>
     */
    public static function discover(): array
    {
        $list = self::discoverViaComposerClassmap();

        if ($list === []) {
            $list = self::discoverViaSourceScan();
        }

        sort($list);

        return array_values(array_unique($list));
    }

    /**
     * @return list<class-string>
     */
    private static function discoverViaComposerClassmap(): array
    {
        $found = [];

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

            if (!str_contains($path, '/Console/Command/')) {
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

            if ($ref->isSubclassOf(Command::class)) {
                if ($ref->getAttributes(AsConsoleCommand::class, ReflectionAttribute::IS_INSTANCEOF) !== []) {
                    $found[] = $class;
                }
            }
        }

        return $found;
    }

    /**
     * @return list<class-string>
     */
    private static function discoverViaSourceScan(): array
    {
        $found = [];

        try {
            if (class_exists(Discover::class)) {
                /** @var iterable<class-string> $classes */
                $classes = Discover::in(base_path('src'))
                    ->extends(Command::class)
                    ->attributes(AsConsoleCommand::class)
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

                    $found[] = $class;
                }
            }
        } catch (Throwable) {
            // ignore
        }

        return $found;
    }
}
