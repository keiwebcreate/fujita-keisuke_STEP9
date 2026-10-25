<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * テスト用の利用者を用意します。fakeは架空データを作る道具で、実在の利用者情報は使いません。
     *
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'name_kanji' => '山田太郎',
            'name_kana' => 'ヤマダタロウ',
            'company_id' => null,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * 会社に所属している利用者を用意します。出品者のテストなどに使います。
     */
    public function withCompany(): static
    {
        return $this->for(CompanyFactory::new(), 'company');
    }

    /**
     * 古いデータなどでカナが未設定の場合を再現します。
     */
    public function withoutKana(): static
    {
        return $this->state(fn (array $attributes): array => ['name_kana' => null]);
    }
}
