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

    /**
     * 指定した人が、この商品をお気に入りに登録しているか調べます。
     */
    public static function isRegisteredForProduct(int $user_id, int $product_id): bool
    {
        return self::query()->where('user_id', $user_id)->where('product_id', $product_id)->exists();
    }

    /**
     * お気に入りを登録、または解除します。
     * 商品をロックして削除などの処理と重ならないようにし、既に登録済みなら重複して作りません。
     */
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

    /**
     * このお気に入りを登録した利用者へのつながりです。
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * お気に入りの対象商品へのつながりです。
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
