<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

/** A disk selection belongs to this instance, never to global process state. */
class MediaStorage
{
    public function __construct(private readonly ?string $disk = null, private readonly string $prefix = '/')
    {
    }

    public function uploadFile(UploadedFile $file, string $path, ?string $fileName = null, bool $withOriginalName = false): string
    {
        $name = $fileName ?? ($withOriginalName ? $file->getClientOriginalName() : $file->hashName());
        $this->validateName($name);
        $stored = Storage::disk($this->disk)->putFileAs($this->prefix . $path, $file, $name);
        if ($stored === false) {
            throw new RuntimeException('The uploaded file could not be stored.');
        }
        return $stored;
    }

    /** @param array<array-key, UploadedFile> $files
     * @param array<array-key, string|null>|null $filesNames
     * @return list<string> */
    public function uploadMultiple(array $files, string $path, ?array $filesNames = null, bool $withOriginalNames = false): array
    {
        $stored = [];
        foreach ($files as $index => $file) {
            $stored[] = $this->uploadFile($file, $path, $filesNames[$index] ?? null, $withOriginalNames);
        }
        return $stored;
    }

    public function uploadBase64Image(string $decodedFile, string $path, ?string $fileName = null): string
    {
        if (preg_match('~\Adata:(image/[a-z0-9.+-]+);base64,(.*)\z~isD', $decodedFile, $parts) !== 1) {
            throw new InvalidArgumentException('Expected a base64 image data URI.');
        }
        $bytes = base64_decode($parts[2], true);
        $image = $bytes !== false && $bytes !== '' ? @getimagesizefromstring($bytes) : false;
        if ($bytes === false || $image === false || strtolower($parts[1]) !== image_type_to_mime_type($image[2])) {
            throw new InvalidArgumentException('Decoded image content does not match its MIME label.');
        }
        $extension = image_type_to_extension($image[2], false);
        if ($extension === false) {
            throw new InvalidArgumentException('The decoded image format is unsupported.');
        }
        $name = $fileName ?? bin2hex(random_bytes(16)) . '.' . $extension;
        $this->validateName($name);
        $target = rtrim($this->prefix . $path, '/') . '/' . $name;
        if (!Storage::disk($this->disk)->put($target, $bytes)) {
            throw new RuntimeException('The decoded image could not be stored.');
        }
        return $target;
    }

    public function deleteFile(string $filePath): bool
    {
        $disk = Storage::disk($this->disk);
        return $disk->exists($filePath) && $disk->delete($filePath);
    }

    public function getFileFullPath(?string $filePath): ?string
    {
        return $filePath === null ? null : Storage::disk($this->disk)->url($filePath);
    }

    private function validateName(string $name): void
    {
        if ($name === '' || $name === '.' || $name === '..' || preg_match('~[\\\\/\x00-\x1f\x7f]~', $name) === 1) {
            throw new InvalidArgumentException('A file name must be a single path component.');
        }
    }
}
