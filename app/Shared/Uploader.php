<?php

namespace App\Shared;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

/**
 * Port of the old ichiman Uploader to Intervention Image v3.
 *
 * Files go to the "public" disk (storage/app/public, served via `php artisan storage:link`
 * at /storage/...). Paths are relative to that disk, e.g. "car/" or "customer/".
 */
class Uploader
{
    private const DISK = 'public';

    protected string $path = '';

    /**
     * Resize to $width (keeping aspect ratio, never upscaling), store it, return the file name.
     */
    public function uploadImage(UploadedFile $file, int $width = 500, ?string $path = null): string
    {
        $path = $this->normalize($path ?? $this->path);
        $extension = strtolower($file->getClientOriginalExtension());

        $image = ImageManager::gd()->read($file->getRealPath());
        $image->scaleDown(width: $width);

        // Same naming scheme as before: <unix time>_<uniqid>.<ext>
        $name = time().'_'.uniqid().'.'.$extension;

        Storage::disk(self::DISK)->put($path.$name, (string) $image->encodeByExtension($extension));

        return $name;
    }

    public function deleteImage(?string $name, ?string $path = null): bool
    {
        if (empty($name)) {
            return false;
        }

        // The old version ignored $path, so files were never actually deleted.
        return Storage::disk(self::DISK)->delete($this->normalize($path ?? $this->path).$name);
    }

    private function normalize(string $path): string
    {
        $path = trim($path, '/');

        return $path === '' ? '' : $path.'/';
    }
}
