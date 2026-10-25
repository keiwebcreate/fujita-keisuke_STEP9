<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use RuntimeException;

#[Fillable(['name', 'name_kanji', 'name_kana', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** @return HasMany<Sale, $this> */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /** @return HasMany<Like, $this> */
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function getMyPageData(): self
    {
        return $this->load([
            'products' => fn (HasMany $relation): HasMany => $relation->orderBy('id'),
            'sales' => fn (HasMany $relation): HasMany => $relation->with('product')
                ->orderBy('created_at')->orderBy('id'),
        ]);
    }

    /** @param array<string, mixed> $validated_data */
    public function updateAccount(array $validated_data): void
    {
        DB::transaction(function () use ($validated_data): void {
            $this->fill([
                'name' => $validated_data['name'],
                'email' => $validated_data['email'],
                'name_kanji' => $validated_data['name_kanji'],
                'name_kana' => $validated_data['name_kana'],
            ]);
            if (! $this->save()) {
                throw new RuntimeException('アカウント情報を更新できませんでした。');
            }
        });
    }

    /** @param array<string, mixed> $validated_data */
    public static function registerUser(array $validated_data): self
    {
        return self::create($validated_data);
    }
}
