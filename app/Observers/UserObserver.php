<?php

namespace App\Observers;

use App\Models\Activity;
use App\Models\User;

class UserObserver
{
    public function created(User $user)
{
    if ($user->role === 'proprietaireparking') {
        Activity::log('register', "Nouveau propriétaire : {$user->name} attend validation",'Système');
    }
}

}
