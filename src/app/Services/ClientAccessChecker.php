<?php

namespace App\Services;

use App\Models\AppClient;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ClientAccessChecker
{
    /**
     * Resolve all model_type variants that may exist in legacy databases.
     *
     * @return array<int, string>
     */
    protected function resolveUserModelTypes(User $user): array
    {
        return array_values(array_unique(array_filter([
            $user->getMorphClass(),
                $user::class,
            config('auth.providers.users.model'),
            'App\\User',
        ])));
    }

    /**
     * Check role access by intersecting user/client role IDs.
     */
    protected function userHasAnyClientRole(User $user, AppClient $client): bool
    {
        $clientRoleIds = $client->roles->pluck('role_id')->filter()->values()->all();

        if (empty($clientRoleIds)) {
            return false;
        }

        $userRoleIds = DB::table('model_has_roles')
            ->whereIn('model_type', $this->resolveUserModelTypes($user))
            ->where('model_id', $user->getKey())
            ->pluck('role_id')
            ->all();

        return !empty(array_intersect($clientRoleIds, $userRoleIds));
    }

    /**
     * Determine if a user can access a specific client based on access mode rules.
     */
    public function userCanAccess(User $user, AppClient $client): bool
    {
        // Load roles relationship if not already loaded
        if (!$client->relationLoaded('roles')) {
            $client->load('roles');
        }

        // dump($client->roles->pluck('name')->toArray());

        // Load allowedUsers relationship if not already loaded
        if (!$client->relationLoaded('allowedUsers')) {
            $client->load('allowedUsers');
        }

        $hasAllowedRole = $this->userHasAnyClientRole($user, $client);

        return match ($client->access_mode) {
            'all' => true,
            'roles' => $hasAllowedRole,
            'users' => $client->allowedUsers->contains($user->id),
            'roles_or_users' => $hasAllowedRole
            || $client->allowedUsers->contains($user->id),
            default => false,
        };
    }
}
