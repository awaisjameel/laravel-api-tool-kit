<?php

declare(strict_types=1);

namespace Essa\APIToolKit;

use Essa\APIToolKit\Support\MediaStorage;
use Illuminate\Http\UploadedFile;

class MediaHelper
{
    public static function uploadFile(UploadedFile $file, string $path, ?string $fileName = null, bool $withOriginalName = false): string
    {
        return static::defaultStorage()->uploadFile($file, $path, $fileName, $withOriginalName);
    }

    /** @param array<array-key, UploadedFile> $files
     * @param array<array-key, string|null>|null $filesNames
     * @return list<string> */
    public static function uploadMultiple(array $files, string $path, ?array $filesNames = null, bool $withOriginalNames = false): array
    {
        return static::defaultStorage()->uploadMultiple($files, $path, $filesNames, $withOriginalNames);
    }

    public static function uploadBase64Image(string $decodedFile, string $path, ?string $fileName = null): string
    {
        return static::defaultStorage()->uploadBase64Image($decodedFile, $path, $fileName);
    }

    public static function deleteFile(string $filePath): bool
    {
        return static::defaultStorage()->deleteFile($filePath);
    }
    public static function getFileFullPath(?string $filePath): ?string
    {
        return static::defaultStorage()->getFileFullPath($filePath);
    }
    public static function disk(string $name): MediaStorage
    {
        return new MediaStorage($name, static::getBasePathPrefix());
    }
    protected static function getBasePathPrefix(): string
    {
        return '/';
    }
    protected static function defaultStorage(): MediaStorage
    {
        return new MediaStorage(null, static::getBasePathPrefix());
    }
}
