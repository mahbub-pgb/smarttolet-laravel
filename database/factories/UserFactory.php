<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mobile' => $this->bdMobile(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'name' => fake()->name(),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'occupation' => fake()->jobTitle(),
            'address' => fake()->address(),
            'role' => Role::User->value,
            'is_phone_verified' => true,
            'is_email_verified' => true,
            // Greater Dhaka-ish coordinates.
            'latitude' => fake()->latitude(23.70, 23.90),
            'longitude' => fake()->longitude(90.34, 90.46),
            'area_preferences' => fake()->randomElements(['Dhanmondi', 'Gulshan', 'Banani', 'Mirpur', 'Uttara'], 2),
        ];
    }

    public function role(Role $role): static
    {
        return $this->state(fn () => ['role' => $role->value]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['is_phone_verified' => false, 'is_email_verified' => false]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['is_suspended' => true]);
    }

    private function bdMobile(): string
    {
        return '01'.fake()->numberBetween(3, 9).fake()->numerify('########');
    }
}
