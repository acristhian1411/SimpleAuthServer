<?php

namespace Tests\Feature;

use App\Models\AppClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_reset_password_with_current_password(): void
    {
        $user = User::factory()->create(['reset_password' => false]);

        Passport::actingAs($user, ['*']);

        $response = $this->postJson('/api/mobile/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertOk();

        $user->refresh();

        $this->assertTrue($user->reset_password);
        $this->assertTrue(Hash::check('new-password', $user->password));
    }

    public function test_reset_requires_the_correct_current_password(): void
    {
        $user = User::factory()->create(['reset_password' => false]);

        Passport::actingAs($user, ['*']);

        $response = $this->postJson('/api/mobile/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('current_password');

        $user->refresh();

        $this->assertFalse($user->reset_password);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_mobile_me_returns_the_reset_password_flag(): void
    {
        $user = User::factory()->create(['reset_password' => false]);

        Passport::actingAs($user, ['*']);

        $this->getJson('/api/mobile/me')
            ->assertOk()
            ->assertJsonPath('reset_password', false);
    }

    public function test_user_endpoint_returns_the_reset_password_flag(): void
    {
        $user = User::factory()->create(['reset_password' => false]);

        Passport::actingAs($user, ['*']);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('reset_password', false);
    }

    public function test_login_response_returns_the_reset_password_flag(): void
    {
        $user = User::factory()->create(['reset_password' => false]);
        $client = AppClient::factory()->asPasswordClient()->create();

        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'password',
            'client_id' => $client->id,
            'client_secret' => $client->plainSecret,
            'username' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()->assertJsonPath('reset_password', false);
    }
}
