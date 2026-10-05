<?php

namespace App\Support;

use App\Models\Unit;
use Illuminate\Support\Str;

final class FractionalQuantityRule
{
    private const DISCRETE = ['piece', 'pieces', 'pc', 'pcs', 'box', 'boxes', 'boite', 'boites', 'carton', 'cartons', 'plaquette', 'plaquettes', 'sachet', 'sachets', 'comprime', 'comprimes'];
    private const FRACTIONAL = ['kg', 'kilogram', 'kilograms', 'kilogramme', 'kilogrammes', 'l', 'litre', 'litres', 'liter', 'liters', 'm', 'metre', 'metres', 'meter', 'meters'];

    public static function classify(?Unit $unit): ?bool
    {
        if (!$unit) {
            return null;
        }

        return self::fromLabels($unit->title, $unit->short_name);
    }

    public static function fromLabels(?string $title, ?string $shortName): ?bool
    {
        $title = self::normalize($title);
        $shortName = self::normalize($shortName);
        $titleRule = self::lookup($title);
        $shortRule = self::lookup($shortName);

        if (($title !== '' && $titleRule === null) || ($shortName !== '' && $shortRule === null)) {
            return null;
        }
        if ($titleRule !== null && $shortRule !== null && $titleRule !== $shortRule) {
            return null;
        }

        return $titleRule ?? $shortRule;
    }

    private static function lookup(string $label): ?bool
    {
        if (in_array($label, self::DISCRETE, true)) {
            return false;
        }
        if (in_array($label, self::FRACTIONAL, true)) {
            return true;
        }

        return null;
    }

    private static function normalize(?string $label): string
    {
        return preg_replace('/[^a-z0-9]+/', '', strtolower(Str::ascii(trim($label ?? '')))) ?? '';
    }
}
