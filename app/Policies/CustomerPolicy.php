<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

/**
 * Autorisations sur les clients.
 *
 * Correspondance capacite -> permission Spatie :
 *   viewAny / view -> customer_view
 *   create         -> customer_create
 *   update         -> customer_update
 *   delete         -> customer_delete
 *   viewSales      -> customer_sales (historique de ventes d'un client)
 */
class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('customer_view');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->can('customer_view');
    }

    public function create(User $user): bool
    {
        return $user->can('customer_create');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->can('customer_update');
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->can('customer_delete');
    }

    /**
     * Consultation des ventes rattachees a un client.
     */
    public function viewSales(User $user, Customer $customer): bool
    {
        return $user->can('customer_sales');
    }
}
