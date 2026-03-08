<?php

namespace App\Policies;

use App\Models\Language;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LanguagePolicy
{
    /**
     * Determine whether the user can view any models.
     * (es. lista di tutte le lingue)
     */
    public function viewAny(User $user): bool|Response
    {
        // Permetti a tutti gli utenti autenticati di vedere la lista
        // (utile per select nel frontend, wishlist, ecc.)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * (es. dettaglio di una lingua specifica)
     */
    public function view(User $user, Language $language): bool|Response
    {
        // Permetti a tutti di vedere il dettaglio (non sensibile)
        return true;
    }

    /**
     * Determine whether the user can create models.
     * (es. aggiungere una nuova lingua)
     */
    public function create(User $user): bool|Response
    {
        // Solo admin possono creare
        if ($user->is_admin) {
            return true;
        }

        // Oppure restituisci un messaggio custom (opzionale)
        return Response::deny('Accesso riservato agli amministratori.');
    }

    /**
     * Determine whether the user can update the model.
     * (es. modificare una lingua esistente)
     */
    public function update(User $user, Language $language): bool|Response
    {
        // Usa la stessa logica di create (solo admin)
        return $this->create($user);
    }

    /**
     * Determine whether the user can delete the model.
     * (es. eliminare una lingua)
     */
    public function delete(User $user, Language $language): bool|Response
    {
        // Usa la stessa logica di create (solo admin)
        return $this->create($user);
    }

    /**
     * Determine whether the user can restore the model.
     * (se usiamo soft deletes)
     */
    public function restore(User $user, Language $language): bool|Response
    {
        return $this->create($user);
    }

    /**
     * Determine whether the user can permanently delete the model.
     * (force delete)
     */
    public function forceDelete(User $user, Language $language): bool|Response
    {
        return $this->create($user);
    }
}
