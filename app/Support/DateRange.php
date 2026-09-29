<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Throwable;

/**
 * Analyse tolerante des filtres de periode.
 *
 * Trois formes coexistent dans l'application :
 *   - `start_date` / `end_date` (formulaires des rapports) ;
 *   - `date_from` / `date_to` (tableau de bord) ;
 *   - `daterange` = "YYYY-MM-DD to YYYY-MM-DD" (ancien selecteur de dates,
 *     encore present dans des liens enregistres).
 *
 * Une valeur invalide est ignoree et le filtre par defaut (30 derniers jours)
 * reste applique : une URL ecrite a la main ne doit jamais provoquer d'erreur 500.
 */
class DateRange
{
    /**
     * Nombre de jours couverts par defaut (jour courant inclus).
     */
    public const DEFAULT_DAYS = 30;

    /**
     * Convertit une valeur "Y-m-d" en date ; renvoie null si elle est invalide.
     */
    public static function parse(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', trim($value))->startOfDay();
        } catch (Throwable $exception) {
            return null;
        }
    }

    /**
     * Periode demandee par la requete : [debut de jour, fin de jour].
     *
     * @param  array{0: string, 1: string}  $keys  noms des parametres attendus
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function resolve(Request $request, array $keys = ['start_date', 'end_date']): array
    {
        [$fromKey, $toKey] = $keys;

        $from = self::parse($request->query($fromKey));
        $to = self::parse($request->query($toKey));

        if ((! $from || ! $to) && $request->has('daterange')) {
            $range = array_map('trim', explode(' to ', (string) $request->query('daterange')));

            if (count($range) === 2) {
                $from = self::parse($range[0]) ?? $from;
                $to = self::parse($range[1]) ?? $to;
            }
        }

        // Periode absente ou inversee : on retombe sur le defaut plutot que de
        // renvoyer un resultat vide.
        if (! $from || ! $to || $from->greaterThan($to)) {
            $to = Carbon::today();
            $from = $to->copy()->subDays(self::DEFAULT_DAYS - 1);
        }

        return [$from->startOfDay(), $to->endOfDay()];
    }
}
