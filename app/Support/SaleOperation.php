<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Validation\ValidationException;

final class SaleOperation
{
    public static function key(array $data): string
    {
        $key = $data['operation_key'] ?? '';
        if (! is_string($key) || ! preg_match('/\A[a-zA-Z0-9_-]{16,64}\z/D', $key)) {
            self::fail('operation_key', 'A valid operation key is required.');
        }

        return $key;
    }

    public static function hash(int $user, int $shop, array $data): string
    {
        $sort = function (array $a) use (&$sort): array {
            if (! array_is_list($a)) {
                ksort($a);
            } foreach ($a as &$v) {
                if (is_array($v)) {
                    $v = $sort($v);
                }
            }

            return $a;
        };

        return hash('sha256', json_encode([$user, $shop, $sort($data)], JSON_THROW_ON_ERROR));
    }

    public static function replay($record, string $hash): void
    {
        if (! hash_equals($record->request_hash, $hash)) {
            self::fail('operation_key', 'This operation key was used with different data.');
        }
    }

    public static function money(mixed $value, string $field = 'amount'): BigDecimal
    {
        $v = MoneyDecimal::parse($value, $field);
        if (! $v->isEqualTo($v->toScale(0, RoundingMode::Down))) {
            self::fail($field, 'XAF payment amounts must be whole francs.');
        }

        return $v;
    }

    public static function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => __($message)]);
    }
}
