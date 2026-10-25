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

    /**
     * 購入記録の追加と在庫の減算を、画面とAPIで共通の処理にします。
     * transactionは「全部成功したら保存し、途中で失敗したら全部戻す」というDBの仕組みです。
     * lockForUpdateで商品の更新を順番待ちにし、最後の1個を2人に売ることを防ぎます。
     * 順番が来てから最新の在庫と出品者を確認し、不足なら409（状態の衝突）で止めます。
     */
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

    /**
     * この購入を行った利用者へのつながりです。
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 削除済みの商品も取り出し、購入履歴に表示できるようにします。
     * 表示する名前や価格は購入時の写しではなく、商品の現在の情報です。
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /**
     * 購入数量を整数として扱います。
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }
}
