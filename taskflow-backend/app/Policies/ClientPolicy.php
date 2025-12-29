<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ClientPolicy
{
    /**
     * Determine whether the user can view any models.
     * Todos los usuarios autenticados pueden ver la lista de clientes
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * Todos los usuarios autenticados pueden ver un cliente específico
     */
    public function view(User $user, Client $client): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     * Solo usuarios admin y manager pueden crear clientes
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    /**
     * Determine whether the user can update the model.
     * Solo usuarios admin y manager pueden actualizar clientes
     */
    public function update(User $user, Client $client): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    /**
     * Determine whether the user can delete the model.
     * Solo usuarios admin pueden eliminar clientes
     */
    public function delete(User $user, Client $client): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can restore the model.
     * Solo usuarios admin pueden restaurar clientes
     */
    public function restore(User $user, Client $client): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     * Solo usuarios admin pueden eliminar permanentemente clientes
     */
    public function forceDelete(User $user, Client $client): bool
    {
        return $user->role === 'admin';
    }
}
