<?php

namespace Database\Factories;

use App\Models\Like;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Like> */
class LikeFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['user_id' => UserFactory::new(), 'product_id' => ProductFactory::new()];
    }
}
