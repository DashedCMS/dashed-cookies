<?php

namespace Dashed\DashedCookies\Policies;

use Dashed\DashedCore\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Het toestemmingslogboek is bewijs: alleen inzien, nooit aanmaken, wijzigen
 * of met de hand verwijderen. Opruimen gebeurt door de bewaartermijn.
 */
class CookieConsentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_cookie_consent');
    }

    public function view(User $user, Model $record): bool
    {
        return $user->can('view_cookie_consent');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Model $record): bool
    {
        return false;
    }

    public function delete(User $user, Model $record): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
