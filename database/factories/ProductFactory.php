<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => UserFactory::new()->withCompany(),
            'company_id' => fn (array $attributes): int => User::findOrFail($attributes['user_id'])->company_id,
            'product_name' => fake()->words(3, true),
            'price' => 1000,
            'stock' => 10,
            'description' => '動作確認用の商品説明です。',
            'img_path' => 'products/sample.png',
        ];
    }

    public function soldOut(): static
    {
        return $this->state(fn (array $attributes): array => ['stock' => 0]);
    }

    public function deleted(): static
    {
        return $this->state(fn (array $attributes): array => ['deleted_at' => now()]);
    }
}
