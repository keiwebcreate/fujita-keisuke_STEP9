<?php

namespace Database\Factories;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Sale> */
class SaleFactory extends Factory
{
    /**
     * テスト用の購入記録を用意します。このデータ作成自体では実際の購入処理や在庫減算は行いません。
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => UserFactory::new(),
            'product_id' => ProductFactory::new(),
            'quantity' => 1,
        ];
    }
}
