<?php

namespace Tests\Feature;

use App\Models\AppClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use App\Models\Role;
use Tests\TestCase;

class ClientAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that API endpoints are accessible with authentication.
     * Note: Full client access control testing is done in ClientAccessCheckerTest.
     * These are basic integration tests to verify the middleware is applied.
     */

    public function test_user_can_access_api_endpoint_with_auth(): void
    {
        $user = User::factory()->create();

        Passport::actingAs($user, ['*']);

        $response = $this->getJson('/api/user');

        // With Passport::actingAs() there is no client_id in token, middleware should allow access.
        $response->assertOk();
    }

    public function test_user_without_auth_cannot_access_api(): void
    {
        $response = $this->getJson('/api/user');

        $this->assertEquals(401, $response->status());
    }

    public function test_mobile_endpoint_requires_auth(): void
    {
        $response = $this->getJson('/api/mobile/me');

        $this->assertEquals(401, $response->status());
    }
}
