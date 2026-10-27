<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Generator\Helpers;

use Essa\APIToolKit\Generator\Configs\PathConfigHandler;
use Essa\APIToolKit\Generator\GeneratedFileInfo;
use Essa\APIToolKit\Generator\ModelNaming;
use Illuminate\Support\Str;

class StubVariablesProvider
{
    /**
     * @return array<string, string>
     */
    public static function generate(string $modelName, string $pathGroup): array
    {
        $configForPathGroup = PathConfigHandler::getFileInfoForAllTypes(
            pathGroupName: $pathGroup,
            modelName: $modelName
        );

        $replacements = [];

        foreach ($configForPathGroup as $type => $generatedFileInfo) {
            $replacements += self::generateReplacementsForType($type, $generatedFileInfo);
        }

        $baseUrlPrefix = self::getBaseUrlPrefix($pathGroup);

        return $replacements + self::generateBasicReplacements($modelName) + $baseUrlPrefix;
    }

    /**
     * @return array<string, string>
     */
    private static function generateReplacementsForType(string $type, GeneratedFileInfo $generatedFileInfo): array
    {
        $namespace = $generatedFileInfo->getNamespace();
        if ($namespace === null || $namespace === '') {
            return [];
        }

        $type = Str::studly($type);

        return [
            "{{Dummy{$type}}}" => $generatedFileInfo->getClassName(),
            "{{Dummy{$type}NameSpace}}" => $namespace,
            "{{Dummy{$type}WithNameSpace}}" => $namespace . '\\' . $generatedFileInfo->getClassName(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function generateBasicReplacements(string $modelName): array
    {
        $tableName = ModelNaming::tableName($modelName);
        $routeName = ModelNaming::routeName($modelName);
        return [
            '{{Dummy}}' => $modelName,
            '{{Dummies}}' => Str::plural($modelName),
            '{{dummy}}' => lcfirst($modelName),
            '{{dummies}}' => lcfirst(Str::plural($modelName)),
            '{{tableNames}}' => $tableName,
            '{{routeName}}' => $routeName,
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function getBaseUrlPrefix(string $pathGroupName): array
    {
        return [
            '{{baseUrlPrefix}}' => PathConfigHandler::getBaseUrlPrefixForGroup($pathGroupName),
        ];
    }
}
