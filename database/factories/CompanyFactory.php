<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Company> */
class CompanyFactory extends Factory
{
    /**
     * テスト用の架空の会社データを用意します。
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['company_name' => fake()->company()];
    }
}
