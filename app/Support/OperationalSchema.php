<?php
namespace App\Support;
use Illuminate\Support\Facades\{DB,Schema};
final class OperationalSchema
{
    private static array $positive=[];
    public static function has(string $table):bool
    {
        $connection=DB::connection();$key=$connection->getName().'|'.$connection->getDatabaseName().'|'.$table;
        if((self::$positive[$key]??0)>microtime(true))return true;
        if(!Schema::hasTable($table))return false; // Do not cache pending migrations.
        self::$positive[$key]=microtime(true)+60;
        return true;
    }
}
