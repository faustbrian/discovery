<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Discovery\Support\Discovery\Persistence;

use Carbon\CarbonInterface;
use Cline\Discovery\Attribute\Persistence\UseReadModel;
use Illuminate\Database\Eloquent\Model;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Finder\Finder;
use Throwable;

use function array_key_exists;
use function array_merge;
use function array_pop;
use function array_search;
use function base_path;
use function class_exists;
use function explode;
use function in_array;
use function is_subclass_of;
use function method_exists;
use function preg_match;
use function str_ends_with;
use function str_replace;

/**
 * Discovers Infrastructure Eloquent models attributed with #[UseReadModel]
 * and returns information needed for ReadModel generation.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ReadModelDiscovery
{
    /**
     * @return array<string, array{
     *     model: class-string,
     *     readModel: class-string,
     *     excludeFields: array<string>,
     *     customMappings: array<string, string>,
     *     fieldMappings: array<string, string>
     * }>
     */
    public static function discover(): array
    {
        $result = [];

        foreach (self::findEloquentModels() as $modelClass) {
            try {
                $ref = new ReflectionClass($modelClass);

                foreach ($ref->getAttributes(UseReadModel::class, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                    /** @var UseReadModel $instance */
                    $instance = $attr->newInstance();

                    $readModelClass = $instance->readModel ?? self::inferReadModelClass($modelClass);

                    $result[$modelClass] = [
                        'model' => $modelClass,
                        'readModel' => $readModelClass,
                        'excludeFields' => $instance->excludeFields,
                        'customMappings' => $instance->customMappings,
                        'fieldMappings' => $instance->fieldMappings,
                    ];
                }
            } catch (ReflectionException) {
                continue;
            }
        }

        return $result;
    }

    /**
     * Get model properties for ReadModel generation
     *
     * @param  class-string                                                        $modelClass
     * @return array<string, array{type: string, nullable: bool, default?: mixed}>
     */
    public static function getModelProperties(string $modelClass): array
    {
        try {
            $model = new $modelClass();

            if (!$model instanceof Model) {
                return [];
            }

            $properties = [];

            // Get fillable attributes
            $fillable = $model->getFillable();

            // Get casts to determine types
            $casts = $model->getCasts();

            // Standard timestamp fields
            $timestamps = ['created_at', 'updated_at'];

            if (method_exists($model, 'getDeletedAtColumn')) {
                $timestamps[] = $model->getDeletedAtColumn();
            }

            // Combine all relevant fields
            $allFields = array_merge($fillable, $timestamps, ['id']);

            foreach ($allFields as $field) {
                $type = 'string'; // default
                $nullable = false;

                // Determine type from casts
                if (array_key_exists($field, $casts)) {
                    $cast = $casts[$field];
                    $type = self::mapCastToPhpType($cast);
                }

                // Special handling for common fields
                if ($field === 'id') {
                    $type = 'string';
                }

                if (in_array($field, $timestamps, true)) {
                    $type = CarbonInterface::class;
                    $nullable = str_ends_with((string) $field, '_at') && $field !== 'created_at' && $field !== 'updated_at';
                }

                $properties[$field] = [
                    'type' => $type,
                    'nullable' => $nullable,
                ];
            }

            return $properties;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array<class-string>
     */
    private static function findEloquentModels(): array
    {
        $finder = new Finder()
            ->files()
            ->in(base_path('src'))
            ->name('*.php')
            ->contains('extends Model');

        $classes = [];

        foreach ($finder as $file) {
            $contents = $file->getContents();

            // Extract namespace
            if (!preg_match('/^namespace\s+(.+?);/m', $contents, $nsMatch)) {
                continue;
            }

            // Extract class name - handle various class declarations including final
            if (!preg_match('/^(?:final\s+)?class\s+(\w+).*extends\s+Model/m', $contents, $classMatch)) {
                continue;
            }

            $namespace = $nsMatch[1];
            $className = $classMatch[1];
            $fullClass = "{$namespace}\\{$className}";

            // Skip classes that might cause autoload issues
            try {
                if (class_exists($fullClass) && is_subclass_of($fullClass, Model::class)) {
                    $classes[] = $fullClass;
                }
            } catch (Throwable) {
                // Skip problematic classes that can't be loaded
                continue;
            }
        }

        return $classes;
    }

    /**
     * @param  class-string $modelClass
     * @return class-string
     */
    private static function inferReadModelClass(string $modelClass): string
    {
        // Convert Infrastructure model to Application ReadModel
        // Example: Cline\BoundedContexts\Finance\Application\Persistence\Model\Invoice
        // Becomes: Cline\BoundedContexts\Finance\Application\ReadModel\Invoice

        $parts = explode('\\', $modelClass);
        $className = array_pop($parts);

        // Find the bounded context
        $contextIndex = array_search('BoundedContexts', $parts, true);

        if ($contextIndex === false) {
            // SharedKernel case
            return str_replace(
                '\\Application\\Persistence\\Model\\',
                '\\Application\\ReadModel\\',
                $modelClass,
            );
        }

        // Bounded context case
        $context = $parts[$contextIndex + 1];

        return "Cline\\BoundedContexts\\{$context}\\Application\\ReadModel\\{$className}";
    }

    private static function mapCastToPhpType(string $cast): string
    {
        return match ($cast) {
            'int', 'integer' => 'int',
            'float', 'double', 'decimal' => 'float',
            'bool', 'boolean' => 'bool',
            'array', 'json' => 'array',
            'datetime', 'timestamp' => CarbonInterface::class,
            'date' => CarbonInterface::class,
            default => 'string',
        };
    }
}
