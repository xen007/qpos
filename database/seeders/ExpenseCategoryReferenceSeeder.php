<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
final class ExpenseCategoryReferenceSeeder extends Seeder
{
    public function run():void
    {
        DB::transaction(function(){
            // Preserve existing categories and deliberate deactivations.
            if(DB::table('expense_categories')->exists())return;
            $now=now('UTC');
            foreach(['Loyer','Électricité et eau','Transport','Entretien et réparations','Télécommunications','Salaires','Fournitures','Autres charges']as$name)
                DB::table('expense_categories')->insert(['name'=>$name,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now]);
        });
    }
}
