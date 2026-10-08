<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\DB;

final class SaleTransaction
{
    /** READ COMMITTED prevents stale journal reads after waiting for a row lock. */
    public static function run(Closure $operation): mixed
    {
        if (DB::transactionLevel() > 0 || DB::getDriverName() !== 'mysql') {
            return DB::transaction($operation, 3);
        }
        $original = DB::selectOne('SELECT @@tx_isolation AS isolation_level')->isolation_level;
        $levels = ['READ-UNCOMMITTED' => 'READ UNCOMMITTED', 'READ-COMMITTED' => 'READ COMMITTED', 'REPEATABLE-READ' => 'REPEATABLE READ', 'SERIALIZABLE' => 'SERIALIZABLE'];
        if (! isset($levels[$original])) {
            throw new \RuntimeException('Unsupported transaction isolation.');
        }
        DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
        try {
            return DB::transaction($operation, 3);
        } finally {
            DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL '.$levels[$original]);
        }
    }
}
