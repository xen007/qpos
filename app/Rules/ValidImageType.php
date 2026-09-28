<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\UploadedFile;

class ValidImageType implements Rule
{
    /**
     * Extensions accepted on the client provided file name.
     */
    protected const ALLOWED_EXTENSIONS = ['jpeg', 'jpg', 'png', 'gif', 'bmp', 'webp'];

    /**
     * Real mime types accepted, read from the file content.
     */
    protected const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/x-ms-bmp', 'image/webp'];

    /**
     * Image types accepted by getimagesize(), as IMAGETYPE_* constants.
     */
    protected const ALLOWED_IMAGE_TYPES = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_BMP, IMAGETYPE_WEBP];

    public function passes($attribute, $value)
    {
        if (!$value instanceof UploadedFile || !$value->isValid()) {
            return false;
        }

        // Client file name extension: kept as a complementary check only
        $extension = strtolower(pathinfo($value->getClientOriginalName(), PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return false;
        }

        // Real mime type, detected from the file content and not from the client name
        if (!in_array(strtolower((string) $value->getMimeType()), self::ALLOWED_MIME_TYPES, true)) {
            return false;
        }

        // The content must be a decodable image of an allowed type
        $imageInfo = @getimagesize($value->getRealPath());

        return $imageInfo !== false && in_array($imageInfo[2], self::ALLOWED_IMAGE_TYPES, true);
    }

    public function message()
    {
        return __('validation.valid_image_type');
    }
}
