<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Generator\Configs;

use Essa\APIToolKit\Generator\Exception\ConfigNotFoundException;
use Essa\APIToolKit\Generator\GeneratedFileInfo;
use Essa\APIToolKit\Generator\ModelNaming;
use Illuminate\Support\Facades\Config;

/** @phpstan-type PathInfo array{folder_path: string, file_name: string, namespace: string|null} */
class PathConfigHandler
{
    /**
     * Get the configuration array associated with a specific path group.
     *
     * @param string $pathGroup The name of the path group.
     *
     * @return array<string, PathInfo>
     */
    public static function getConfigForPathGroup(string $pathGroup): array
    {
        $paths = Config::get("api-tool-kit.groups_files_paths.{$pathGroup}", []);
        if (!is_array($paths)) {
            throw new ConfigNotFoundException('Path group must be a map.');
        }
        $validated = [];
        foreach ($paths as $type => $path) {
            if (!is_string($type) || !is_array($path) || !is_string($path['folder_path'] ?? null) || !is_string($path['file_name'] ?? null)
                || (!is_string($path['namespace'] ?? null) && ($path['namespace'] ?? null) !== null)) {
                throw new ConfigNotFoundException('Invalid generator path configuration.');
            }
            $validated[$type] = ['folder_path' => $path['folder_path'], 'file_name' => $path['file_name'], 'namespace' => $path['namespace'] ?? null];
        }
        return $validated;
    }

    /**
     * Get information about the file path associated with a specific path group and type.
     *
     * @param string $pathGroupName The name of the path group.
     * @param string $fileType The type of the file.
     *
     * @throws ConfigNotFoundException If the path information is not found for the specified type in the config file.
     *
     * @return PathInfo */
    public static function getFilePathInfo(string $pathGroupName, string $fileType): array
    {
        $config = self::getConfigForPathGroup($pathGroupName);

        $filePathInfo = $config[$fileType] ?? null;

        if (! $filePathInfo) {
            throw new ConfigNotFoundException("File path information not found for type: {$fileType}");
        }

        return $filePathInfo;
    }

    /**
     * Create an instance of a GeneratedFileInfo based on the specified path group, type, and model.
     *
     * @param string $pathGroupName The name of the path group.
     * @param string $generatedFileType The type of the generated file.
     * @param string $modelName The model associated with the path resolver.
     *
     * @return GeneratedFileInfo An instance of GeneratedFileInfo.
     *
     * @throws ConfigNotFoundException If the path information is not found for the specified type in the config file.
     */
    public static function generateFilePathInfo(string $pathGroupName, string $generatedFileType, string $modelName): GeneratedFileInfo
    {
        $pathInfo = self::getFilePathInfo($pathGroupName, $generatedFileType);
        $folder = self::substituteModelValues($modelName, $pathInfo['folder_path']);
        $fileName = self::substituteModelValues($modelName, $pathInfo['file_name']);
        if ($generatedFileType === 'migration' && str_ends_with($fileName, '_create_' . ModelNaming::tableName($modelName) . '_table.php')) {
            $existing = glob($folder . '/*_create_' . ModelNaming::tableName($modelName) . '_table.php');
            if ($existing === false || count($existing) > 1) {
                throw new ConfigNotFoundException('Cannot identify one existing create-table migration.');
            }
            if (count($existing) === 1) {
                $fileName = basename($existing[0]);
            }
        }

        return new GeneratedFileInfo(
            fileName: $fileName,
            folderPath: $folder,
            namespace: $pathInfo['namespace'] ? self::substituteModelValues($modelName, $pathInfo['namespace']) : null
        );
    }

    /**
     * Get an array of GeneratedFileInfo instances for all types in a specific path group.
     *
     * @param string $pathGroupName The name of the path group.
     * @param string $modelName The model associated with the path resolver.
     *
     * @return array<string, GeneratedFileInfo>
     *
     * @throws ConfigNotFoundException
     */
    public static function getFileInfoForAllTypes(string $pathGroupName, string $modelName): array
    {
        $config = self::getConfigForPathGroup($pathGroupName);

        $generatedFilePaths = [];

        foreach ($config as $fileType => $filePathInfo) {
            $generatedFilePaths[$fileType] = self::generateFilePathInfo($pathGroupName, $fileType, $modelName);
        }

        return $generatedFilePaths;
    }

    /**
     * Get the base URL prefix for a specific route group.
     *
     * @param string $pathGroupName The name of the path group.
     *
     * @return string The base URL prefix for the specified group.
     *
     * @throws ConfigNotFoundException If the base URL prefix is not found for the specified group.
     */
    public static function getBaseUrlPrefixForGroup(string $pathGroupName): string
    {
        $routeGroupBaseURLs = Config::get('api-tool-kit.groups_url_prefixes', []);

        $baseURL = $routeGroupBaseURLs[$pathGroupName] ?? null;

        if (null === $baseURL) {
            throw new ConfigNotFoundException("Base URL prefix not found for route group: {$pathGroupName}");
        }

        return $baseURL;
    }

    /**
     * Get the default path group from the configuration.
     *
     * @return string The name of the default path group.
     */
    public static function getDefaultPathGroup(): string
    {
        return Config::get('api-tool-kit.default_group', 'default');
    }

    /**
     * Get all path group names from the configuration.
     *
     * @return list<string>
     */
    public static function getAllPathGroups(): array
    {
        $groups = Config::get('api-tool-kit.groups_files_paths', []);
        if (!is_array($groups)) {
            throw new ConfigNotFoundException('Path groups must be a map.');
        }
        return array_values(array_filter(array_keys($groups), is_string(...)));
    }

    /**
     * Check if a path group exists or not.
     *
     * @param string $groupName The name of the path group.
     * @return bool True if the path group exists, false otherwise.
     */
    public static function isValidPathGroup(string $groupName): bool
    {
        return (bool) Config::get("api-tool-kit.groups_files_paths.{$groupName}");
    }

    /**
     * Replace placeholders in a string with actual model-related values.
     *
     * @param string $modelName The model name.
     * @param string $string The string containing placeholders.
     * @return string The string with placeholders replaced.
     */
    private static function substituteModelValues(string $modelName, string $string): string
    {
        return strtr(
            $string,
            [
                '{ModelName}' => $modelName,
                '{TableName}' => ModelNaming::tableName($modelName),
            ]
        );
    }
}
