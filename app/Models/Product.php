<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'company_id', 'product_name', 'price', 'stock', 'description', 'img_path'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    /** @return Collection<int, Product> */
    public static function getListedProductsForUser(int $user_id): Collection
    {
        return self::query()->where('user_id', '!=', $user_id)->orderBy('id')->get();
    }

    public static function getProductDetail(string $product_id): self
    {
        return self::query()->with('company')->findOrFail($product_id);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['price' => 'integer', 'stock' => 'integer'];
    }
}
