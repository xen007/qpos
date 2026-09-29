<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Autorisations sur les produits.
 *
 * Correspondance capacite -> permission Spatie :
 *   viewAny / view -> product_view
 *   create         -> product_create
 *   update         -> product_update
 *   delete         -> product_delete
 *   import         -> product_import
 *
 * Les routes portent deja ces permissions (voir App\Support\PermissionRoutes) :
 * la politique fournit le meme controle au niveau du modele, pour les
 * controleurs (authorize) et les vues (@can) lors de la migration.
 */
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('product_view');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->can('product_view');
    }

    public function create(User $user): bool
    {
        return $user->can('product_create');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can('product_update');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->can('product_delete');
    }

    /**
     * Import de produits par fichier.
     */
    public function import(User $user): bool
    {
        return $user->can('product_import');
    }
}
