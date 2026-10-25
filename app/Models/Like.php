<?php

namespace App\Models;

use Database\Factories\LikeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

#[Fillable(['user_id', 'product_id'])]
class Like extends Model
{
    /** @use HasFactory<LikeFactory> */
    use HasFactory;

    public static function isRegisteredForProduct(int $user_id, int $product_id): bool
    {
        return self::query()->where('user_id', $user_id)->where('product_id', $product_id)->exists();
    }

    public static function setForProduct(int $user_id, string $product_id, bool $liked): bool
    {
        return DB::transaction(function () use ($user_id, $product_id, $liked): bool {
            $product = Product::query()->lockForUpdate()->findOrFail($product_id);
            $attributes = ['user_id' => $user_id, 'product_id' => $product->id];
            if ($liked) {
                self::query()->firstOrCreate($attributes);
            } else {
                self::query()->where($attributes)->delete();
            }

            return $liked;
        });
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
