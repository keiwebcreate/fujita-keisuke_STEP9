<?php

namespace App\Models;

use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use RuntimeException;

#[Fillable(['user_id', 'product_id', 'quantity'])]
class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory;

    public static function purchase(int $buyer_id, string $product_id, int $quantity): self
    {
        abort_if($quantity < 1 || $quantity > 2147483647, 422, '購入数量が不正です。');

        return DB::transaction(function () use ($buyer_id, $product_id, $quantity): self {
            $product = Product::query()->lockForUpdate()->findOrFail($product_id);
            abort_if($product->user_id === $buyer_id, 403, '自分の商品は購入できません。');
            abort_if($product->stock < $quantity, 409, '在庫が不足しています。');
            $sale = new self(['user_id' => $buyer_id, 'product_id' => $product->id, 'quantity' => $quantity]);
            if (! $sale->save()) {
                throw new RuntimeException('購入記録を保存できませんでした。');
            }
            $product->stock -= $quantity;
            if (! $product->save()) {
                throw new RuntimeException('在庫を更新できませんでした。');
            }

            return $sale->setRelation('product', $product);
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
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }
}
