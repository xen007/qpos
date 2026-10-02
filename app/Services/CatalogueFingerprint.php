<?php
namespace App\Services;

use Illuminate\Database\Connection;

class CatalogueFingerprint
{
    private const EXCLUDED = ['migrations', 'permissions', 'role_has_permissions', 'cache', 'cache_locks', 'sessions', 'product_units', 'product_barcodes', 'price_rules', 'promotions', 'catalogue_conversion_runs', 'catalogue_conversion_issues', 'catalogue_conversion_mappings'];
    public function snapshot(Connection $connection, ?array $specification = null, bool $allTables = false): array
    {
        if ($specification === null) {
            $specification = [];
            foreach ($connection->select('SHOW TABLES') as $row) {
                $table = (string) array_values((array) $row)[0];
                if ($allTables || !in_array($table, self::EXCLUDED, true)) {
                    $columns = $connection->getSchemaBuilder()->getColumnListing($table);
                    if (!$allTables && $table === 'products') { $columns = array_values(array_diff($columns, ['catalogue_price_ttc', 'catalogue_reference_cost'])); }
                    $specification[$table] = ['columns' => $columns];
                }
            }
        }
        $result = [];
        foreach ($specification as $table => $spec) {
            $columns = $spec['columns'];
            $query = $connection->table($table)->select($columns);
            foreach ($columns as $column) { $query->orderBy($column); }
            $hash = hash_init('sha256');
            $count = 0;
            foreach ($query->cursor() as $row) {
                hash_update($hash, json_encode((array) $row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION)."\n");
                $count++;
            }
            $result[$table] = ['columns' => $columns, 'count' => $count, 'sha256' => hash_final($hash)];
        }
        return $result;
    }
}
