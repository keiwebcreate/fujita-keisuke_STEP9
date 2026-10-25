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
use Illuminate\Support\Facades\DB;
use RuntimeException;

#[Fillable(['user_id', 'company_id', 'product_name', 'price', 'stock', 'description', 'img_path'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @param  array{product_name?: string|null, min_price?: int|string|null, max_price?: int|string|null}  $filters
     * @return Collection<int, Product>
     */
    public static function getListedProductsForUser(int $user_id, array $filters = []): Collection
    {
        $query = self::query()->where('user_id', '!=', $user_id);
        if (isset($filters['product_name']) && $filters['product_name'] !== '') {
            $keyword = strtr($filters['product_name'], ['!' => '!!', '%' => '!%', '_' => '!_']);
            $query->whereRaw("product_name LIKE ? ESCAPE '!'", ['%'.$keyword.'%']);
        }
        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }
        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        return $query->orderBy('id')->get();
    }

    public static function getProductDetail(string $product_id): self
    {
        return self::query()->with('company')->findOrFail($product_id);
    }

    /** @param array<string, mixed> $validated_data */
    public static function registerProduct(User $user, array $validated_data, string $image_path): self
    {
        return self::create([
            'product_name' => $validated_data['product_name'],
            'description' => $validated_data['description'],
            'price' => $validated_data['price'],
            'stock' => $validated_data['stock'],
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'img_path' => $image_path,
        ]);
    }

    public static function getOwnedProductDetail(string $product_id, int $user_id): self
    {
        $product = self::getProductDetail($product_id);
        abort_unless($product->user_id === $user_id, 403);

        return $product;
    }

    /** @param array<string, mixed> $validated_data */
    public function updateForOwner(int $user_id, array $validated_data, ?string $image_path): ?string
    {
        return DB::transaction(function () use ($user_id, $validated_data, $image_path): ?string {
            $product = self::query()->lockForUpdate()->findOrFail($this->id);
            abort_unless($product->user_id === $user_id, 403);
            $old_image_path = $product->img_path;
            $product->fill([
                'product_name' => $validated_data['product_name'],
                'description' => $validated_data['description'],
                'price' => $validated_data['price'],
                'stock' => $validated_data['stock'],
            ]);
            if ($image_path !== null) {
                $product->img_path = $image_path;
            }
            if (! $product->save()) {
                throw new RuntimeException('商品を更新できませんでした。');
            }

            return $image_path !== null ? $old_image_path : null;
        });
    }

    public function deleteForOwner(int $user_id): void
    {
        DB::transaction(function () use ($user_id): void {
            $product = self::query()->lockForUpdate()->findOrFail($this->id);
            abort_unless($product->user_id === $user_id, 403);
            if (! $product->delete()) {
                throw new RuntimeException('商品を削除できませんでした。');
            }
        });
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
