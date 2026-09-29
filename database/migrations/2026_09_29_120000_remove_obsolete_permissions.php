<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Config;

/**
 * Supprime deux permissions devenues inutiles :
 *   - sale_edit      : doublon de sale_update, controlee nulle part ;
 *   - product_purchase : accordee au caissier, utilisee nulle part.
 *
 * Le seeder ne les cree plus. Les affectations de roles sont retirees avant la
 * suppression, pour ne dependre d'aucune contrainte de cle etrangere.
 */
return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $obsolete = ['sale_edit', 'product_purchase'];

    public function up(): void
    {
        $model = Config::get('permission.models.permission');

        foreach ($this->obsolete as $name) {
            foreach ($model::query()->where('name', $name)->get() as $permission) {
                foreach ($permission->roles as $role) {
                    $role->revokePermissionTo($permission);
                }

                foreach ($permission->users as $user) {
                    $user->revokePermissionTo($permission);
                }

                $permission->delete();
            }
        }
    }

    public function down(): void
    {
        $model = Config::get('permission.models.permission');

        foreach ($this->obsolete as $name) {
            $model::findOrCreate($name, 'web');
        }
    }
};
