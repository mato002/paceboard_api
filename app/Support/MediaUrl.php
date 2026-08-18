<?php

namespace App\Support;

use App\Models\TripPhoto;

final class MediaUrl
{
    public static function photo(TripPhoto $photo): string
    {
        return url('/api/media/photos/'.$photo->id);
    }

    public static function path(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $path = str_replace('\\', '/', trim($path));

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $storagePos = strpos($path, '/storage/');
            if ($storagePos === false) {
                return $path;
            }
            $path = substr($path, $storagePos + strlen('/storage/'));
        }

        $path = ltrim($path, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        if ($path === '') {
            return null;
        }

        return url('/api/media/files/'.$path);
    }
}
