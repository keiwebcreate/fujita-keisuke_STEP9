<?php

namespace Database\Factories;

use App\Models\Like;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Like> */
class LikeFactory extends Factory
{
    /**
     * テスト用のお気に入りと、その利用者・商品を用意します。
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['user_id' => UserFactory::new(), 'product_id' => ProductFactory::new()];
    }
}
