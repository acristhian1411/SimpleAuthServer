<?php

namespace Database\Factories;

use App\Models\AppClient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppClient>
 */
class AppClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'secret' => \Illuminate\Support\Str::random(40),
            'provider' => 'users',
            'redirect' => null,
            'personal_access_client' => false,
            'password_client' => true,
            'revoked' => false,
            'access_mode' => 'all',
        ];
    }
}
