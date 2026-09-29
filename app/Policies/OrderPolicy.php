<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * Autorisations sur les commandes.
 *
 * Correspondance capacite -> permission Spatie :
 *   viewAny / view -> sale_view
 *   create         -> sale_create
 *   update         -> sale_update
 *   delete         -> sale_delete
 *   collect        -> sale_update (encaissement d'un solde)
 *
 * Remarque sur update : la permission sale_edit, qui doublonnait sale_update sans
 * etre controlee nulle part, a ete supprimee. Si le role vendeur doit un jour
 * modifier des ventes, il suffit de lui accorder sale_update.
 *
 * L'application enforce aujourd'hui ces droits par les middlewares de route
 * (permission:sale_*) ; cette politique sert de reference unique pour les
 * controleurs (authorize) et pour les vues lors de la migration (@can).
 */
class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sale_view');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->can('sale_view');
    }

    public function create(User $user): bool
    {
        return $user->can('sale_create');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->can('sale_update');
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->can('sale_delete');
    }

    /**
     * Encaissement du solde d'une commande.
     */
    public function collect(User $user, Order $order): bool
    {
        return $user->can('sale_update');
    }
}
