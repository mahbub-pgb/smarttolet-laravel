<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Listing>
 */
class ListingFactory extends Factory
{
    protected $model = Listing::class;

    /**
     * Real Dhaka neighbourhoods with approximate centre coordinates.
     *
     * @var array<int, array{name: string, lat: float, lng: float}>
     */
    private array $areas = [
        ['name' => 'Dhanmondi', 'lat' => 23.7461, 'lng' => 90.3742],
        ['name' => 'Gulshan', 'lat' => 23.7925, 'lng' => 90.4078],
        ['name' => 'Banani', 'lat' => 23.7937, 'lng' => 90.4066],
        ['name' => 'Mirpur', 'lat' => 23.8223, 'lng' => 90.3654],
        ['name' => 'Uttara', 'lat' => 23.8759, 'lng' => 90.3795],
        ['name' => 'Mohammadpur', 'lat' => 23.7654, 'lng' => 90.3576],
        ['name' => 'Bashundhara R/A', 'lat' => 23.8198, 'lng' => 90.4264],
        ['name' => 'Motijheel', 'lat' => 23.7330, 'lng' => 90.4172],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $area = fake()->randomElement($this->areas);
        $type = fake()->randomElement(['apartment', 'room', 'sublet', 'office', 'shop', 'house']);
        $title = ucfirst($type)." for rent in {$area['name']}";

        return [
            'owner_id' => User::factory(),
            'title' => $title,
            'description' => fake()->paragraphs(3, true),
            'type' => $type,
            'category' => fake()->randomElement(['family', 'bachelor', 'commercial', null]),
            'rent' => fake()->numberBetween(6000, 60000),
            'bedrooms' => fake()->numberBetween(1, 5),
            'bathrooms' => fake()->numberBetween(1, 4),
            'area_name' => $area['name'],
            'address' => fake()->buildingNumber().', '.$area['name'].', Dhaka',
            'latitude' => $area['lat'] + fake()->randomFloat(4, -0.01, 0.01),
            'longitude' => $area['lng'] + fake()->randomFloat(4, -0.01, 0.01),
            'amenities' => fake()->randomElements(['wifi', 'lift', 'parking', 'generator', 'gas', 'security', 'balcony'], 4),
            'images' => [],
            'status' => Listing::STATUS_APPROVED,
            'approved_at' => now(),
            'expires_at' => now()->addDays(30),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => Listing::STATUS_PENDING, 'approved_at' => null, 'expires_at' => null]);
    }

    public function ownedBy(User $user): static
    {
        return $this->state(fn () => ['owner_id' => $user->id]);
    }
}
