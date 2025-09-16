<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Discovery\Support;

use Cline\Discovery\Support\Discovery\Persistence\ReadModelDiscovery;

use function array_keys;
use function class_basename;
use function implode;
use function lcfirst;
use function str_replace;

/**
 * Generates toReadModel() mapping methods for Eloquent Finders
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ReadModelMappingGenerator
{
    /**
     * Generate toReadModel() method code for a specific model
     *
     * @param class-string $modelClass
     * @param class-string $readModelClass
     * @param array{
     *     excludeFields: array<string>,
     *     customMappings: array<string, string>,
     *     fieldMappings: array<string, string>
     * } $config
     */
    public static function generateToReadModelMethod(
        string $modelClass,
        string $readModelClass,
        array $config = [],
    ): string {
        $properties = ReadModelDiscovery::getModelProperties($modelClass);

        // Apply exclusions and mappings
        foreach ($config['excludeFields'] ?? [] as $field) {
            unset($properties[$field]);
        }

        // Generate parameter list
        $params = [];
        $fieldMappings = $config['fieldMappings'] ?? [];

        foreach (array_keys($properties) as $field) {
            $targetField = $fieldMappings[$field] ?? $field;
            $sourceValue = self::generateFieldMapping($field, $config['customMappings'][$field] ?? null);

            $params[] = "{$targetField}: {$sourceValue}";
        }

        $paramString = implode(",\n            ", $params);
        $readModelShortClass = class_basename($readModelClass);
        $modelParam = '$'.lcfirst(class_basename($modelClass));

        return <<<PHP
    private static function toReadModel({$modelClass} {$modelParam}): {$readModelShortClass}
    {
        return new {$readModelShortClass}(
            {$paramString},
        );
    }
PHP;
    }

    /**
     * Generate complete Finder class with toReadModel method
     *
     * @param class-string $modelClass
     * @param class-string $readModelClass
     * @param array{
     *     excludeFields: array<string>,
     *     customMappings: array<string, string>,
     *     fieldMappings: array<string, string>
     * } $config
     */
    public static function generateFinderClass(
        string $modelClass,
        string $readModelClass,
        string $finderNamespace,
        array $config = [],
    ): string {
        $finderClassName = class_basename($readModelClass).'Finder';
        $readModelShortClass = class_basename($readModelClass);
        $modelShortClass = class_basename($modelClass);
        $interfaceClass = str_replace('\\Infrastructure\\', '\\Application\\', $finderNamespace).'\\'.$finderClassName.'Interface';
        $interfaceShortClass = class_basename($interfaceClass);

        $toReadModelMethod = self::generateToReadModelMethod($modelClass, $readModelClass, $config);

        return <<<PHP
<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust - All Rights Reserved
 *
 * Unauthorized copying, distribution, or use of this file in any manner
 * is strictly prohibited. This material is proprietary and confidential.
 */

namespace {$finderNamespace};

use {$modelClass};
use {$readModelClass};
use {$interfaceClass};

/**
 * Eloquent implementation of {$readModelShortClass} finder - returns ReadModels for queries
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Eloquent{$finderClassName} implements {$interfaceShortClass}
{
    public function find(string \$id): ?{$readModelShortClass}
    {
        \$model = {$modelShortClass}::find(\$id);

        return \$model ? self::toReadModel(\$model) : null;
    }

    public function search(array \$criteria): array
    {
        \$models = {$modelShortClass}::where(\$criteria)->get();

        return \$models->map(fn (\$model) => self::toReadModel(\$model))->toArray();
    }

    public function count(array \$criteria = []): int
    {
        return {$modelShortClass}::where(\$criteria)->count();
    }

{$toReadModelMethod}
}
PHP;
    }

    /**
     * Generate field mapping expression
     */
    private static function generateFieldMapping(string $field, ?string $customType = null): string
    {
        $modelParam = '$model';

        // Handle special field mappings
        return match ($field) {
            'id' => "(string) {$modelParam}->{$field}",
            'created_at', 'updated_at', 'deleted_at' => "{$modelParam}->{$field}",
            default => match ($customType) {
                'int', 'integer' => "(int) {$modelParam}->{$field}",
                'float', 'double' => "(float) {$modelParam}->{$field}",
                'bool', 'boolean' => "(bool) {$modelParam}->{$field}",
                'array' => "{$modelParam}->{$field} ?? []",
                'string' => "{$modelParam}->{$field} ?? ''",
                default => "{$modelParam}->{$field}",
            },
        };
    }
}
