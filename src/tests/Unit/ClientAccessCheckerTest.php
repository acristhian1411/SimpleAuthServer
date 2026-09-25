<?php

namespace Tests\Unit;

use App\Models\AppClient;
use App\Models\User;
use App\Services\ClientAccessChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClientAccessCheckerTest extends TestCase
{
    use RefreshDatabase;

    private ClientAccessChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = app(ClientAccessChecker::class);
    }

    public function test_user_can_access_client_with_all_mode(): void
    {
        $user = User::factory()->create();
        $client = AppClient::factory()->create(['access_mode' => 'all']);

        $this->assertTrue($this->checker->userCanAccess($user, $client));
    }

    public function test_user_with_role_can_access_client_with_roles_mode(): void
    {
        $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole($role);

        $client = AppClient::factory()->create(['access_mode' => 'roles']);
        $client->roles()->attach($role->getKey());

        $this->assertTrue($this->checker->userCanAccess($user, $client));
    }

    public function test_user_with_legacy_model_type_can_access_client_with_roles_mode(): void
    {
        $role = Role::create(['name' => 'admin_legacy', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole($role);

        DB::table('model_has_roles')
            ->where('role_id', $role->getKey())
            ->where('model_id', $user->getKey())
            ->where('model_type', $user->getMorphClass())
            ->update(['model_type' => 'App\\User']);

        $client = AppClient::factory()->create(['access_mode' => 'roles']);
        $client->roles()->attach($role->getKey());

        $this->assertTrue($this->checker->userCanAccess($user, $client));
    }

    public function test_user_without_role_cannot_access_client_with_roles_mode(): void
    {
        $user = User::factory()->create();
        $client = AppClient::factory()->create(['access_mode' => 'roles']);

        $this->assertFalse($this->checker->userCanAccess($user, $client));
    }

    public function test_user_in_allowed_users_can_access_client_with_users_mode(): void
    {
        $user = User::factory()->create();
        $client = AppClient::factory()->create(['access_mode' => 'users']);
        $client->allowedUsers()->attach($user->id);

        $this->assertTrue($this->checker->userCanAccess($user, $client));
    }

    public function test_user_not_in_allowed_users_cannot_access_client_with_users_mode(): void
    {
        $user = User::factory()->create();
        $client = AppClient::factory()->create(['access_mode' => 'users']);

        $this->assertFalse($this->checker->userCanAccess($user, $client));
    }

    public function test_user_with_role_can_access_client_with_roles_or_users_mode(): void
    {
        $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $client = AppClient::factory()->create(['access_mode' => 'roles_or_users']);
        $client->roles()->attach($role->id);

        $this->assertTrue($this->checker->userCanAccess($user, $client));
    }

    public function test_user_in_allowed_users_can_access_client_with_roles_or_users_mode(): void
    {
        $user = User::factory()->create();
        $client = AppClient::factory()->create(['access_mode' => 'roles_or_users']);
        $client->allowedUsers()->attach($user->id);

        $this->assertTrue($this->checker->userCanAccess($user, $client));
    }

    public function test_user_with_neither_role_nor_in_users_cannot_access_client_with_roles_or_users_mode(): void
    {
        $user = User::factory()->create();
        $client = AppClient::factory()->create(['access_mode' => 'roles_or_users']);

        $this->assertFalse($this->checker->userCanAccess($user, $client));
    }
}
