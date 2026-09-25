<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Passport\Client;

class AppClient extends Client
{
    /**
     * Get the roles that are allowed to access this client.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'oauth_client_roles',
            'client_id',
            'role_id',
            'id',
            'role_id'
        );
    }

    /**
     * Get the users that are allowed to access this client.
     */
    public function allowedUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'oauth_client_users',
            'client_id',
            'user_id'
        );
    }
}
