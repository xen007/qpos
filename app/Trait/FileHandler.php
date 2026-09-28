<?php

namespace App\Trait;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Facades\Image;

class FileHandler
{
    public function uploadToPublic($file, $path = "/assets/images")
    {
        $file_name = time() . "_" . uniqid() . "_" . $file->getClientOriginalName();
        $storingPath = public_path() . $path . "/" . $file_name;

        if (!file_exists($path)) {
            Storage::makeDirectory($path);
        }

        Image::make($file->getRealPath())->resize(null, 400, function ($constraint) {
            $constraint->aspectRatio();
        })->save($storingPath);

        return $path . "/" . $file_name;
    }

    public function securePublicUnlink($path)
    {
        $absolute_path = public_path($path);

        if (file_exists($absolute_path) && is_file($absolute_path)) {
            unlink($absolute_path);
            return true;
        } else {
            return false;
        }
    }

    public function secureUnlink($path)
    {
        $absolute_path = storage_path() . '/app/public/' . $path;

        if (file_exists($absolute_path) && is_file($absolute_path)) {
            unlink($absolute_path);
            return true;
        } else {
            return false;
        }
    }

    public function fileUploadAndGetPath($file, $path = "/public/media/others")
    {
        // Extension deduced from the real mime type, never from the client file name
        $extension = $this->extensionFromMimeType($file->getMimeType());

        // File name generated server side only
        $file_name = time() . "_" . bin2hex(random_bytes(16)) . "." . $extension;
        $storingPath = storage_path() . "/app" . $path . "/" . $file_name;

        File::ensureDirectoryExists(dirname($storingPath));

        // Re-encode the image so any payload added to the uploaded file is dropped
        Image::make($file->getRealPath())->save($storingPath);

        // Remove Public from link
        return substr($path . "/" . $file_name, 8);
    }

    /**
     * Resolve the file extension matching a real mime type.
     */
    protected function extensionFromMimeType($mimeType)
    {
        $extensions = [
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/gif" => "gif",
            "image/bmp" => "bmp",
            "image/x-ms-bmp" => "bmp",
            "image/webp" => "webp",
        ];

        $extension = $extensions[strtolower((string) $mimeType)] ?? null;

        if (is_null($extension)) {
            throw ValidationException::withMessages([
                'image' => __('validation.invalid_image_content'),
            ]);
        }

        return $extension;
    }
}
