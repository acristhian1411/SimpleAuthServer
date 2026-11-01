<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PassportLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_find_for_passport_matches_ignoring_case_and_spaces(): void
    {
        $user = User::factory()->create(['email' => 'Cristhian.Tester@Example.com']);

        $found = (new User)->findForPassport('  cristhian.tester@example.COM ');

        $this->assertTrue($found?->is($user));
    }

    public function test_find_for_passport_returns_null_for_unknown_email(): void
    {
        User::factory()->create(['email' => 'known@example.com']);

        $this->assertNull((new User)->findForPassport('unknown@example.com'));
    }
}
