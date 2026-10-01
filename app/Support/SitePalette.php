<?php

namespace App\Support;

final class SitePalette
{
    public static function options(): array
    {
        return [
            'petrol' => ['label' => __('Petrol blue'), 'color' => '#1E5F74'],
            'teal' => ['label' => __('Teal'), 'color' => '#0F766E'],
            'indigo' => ['label' => __('Indigo'), 'color' => '#4338CA'],
        ];
    }

    public static function current(): string
    {
        $palette = config('system.site_palette');

        return is_string($palette) && array_key_exists($palette, self::options())
            ? $palette : 'petrol';
    }
}
