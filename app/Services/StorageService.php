<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class StorageService
{
    public function url(string $path): string
    {
        $disk = config('filesystems.default', 'local');

        return Storage::disk($disk)->url($path);
    }

    public function temporaryUrl(string $path, int $expiresInMinutes = 60): string
    {
        $disk = config('filesystems.default', 'local');

        return Storage::disk($disk)->temporaryUrl(
            $path,
            now()->addMinutes($expiresInMinutes)
        );
    }

    public function store(UploadedFile $file, string $directory): string
    {
        $disk = config('filesystems.default', 'local');

        return $file->store($directory, $disk);
    }

    public function delete(string $path): bool
    {
        $disk = config('filesystems.default', 'local');

        return Storage::disk($disk)->delete($path);
    }
}
