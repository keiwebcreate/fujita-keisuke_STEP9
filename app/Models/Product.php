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
     * 自分以外の商品から、指定された名前や価格に合うものを商品番号順で取り出します。
     * 名前の「%」「_」は検索の特別な記号ではなく、その文字自体として探します。
     *
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

    /**
     * 商品番号から商品と会社をまとめて取り出します。見つからなければ404（見つからない）にします。
     */
    public static function getProductDetail(string $product_id): self
    {
        return self::query()->with('company')->findOrFail($product_id);
    }

    /**
     * 購入対象を取り出し、自分の出品商品なら403（操作できない）で止めます。
     * 在庫の最終確認は、実際に購入を保存するSaleモデルでも行います。
     */
    public static function getPurchasableProduct(string $product_id, int $buyer_id): self
    {
        $product = self::getProductDetail($product_id);
        abort_if($product->user_id === $buyer_id, 403, '自分の商品は購入できません。');

        return $product;
    }

    /**
     * 入力チェックを通った商品情報を保存します。
     * 出品者と会社は送られた値を信用せず、ログインしている人の情報を使います。
     *
     * @param  array<string, mixed>  $validated_data
     */
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

    /**
     * 出品者本人かを確認します。URLの商品番号を書き換えても他人の商品は操作できません。
     */
    public static function getOwnedProductDetail(string $product_id, int $user_id): self
    {
        $product = self::getProductDetail($product_id);
        abort_unless($product->user_id === $user_id, 403);

        return $product;
    }

    /**
     * 本人の商品をロックし、まとめて更新します。ロック中は他の更新処理を待たせます。
     * 画像未選択なら元の画像を残し、画像変更時は片付けるために古い画像の保存先を返します。
     *
     * @param  array<string, mixed>  $validated_data
     */
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

    /**
     * 出品者本人か再確認し、商品に削除日時を付けます。
     * これは「論理削除」といい、購入履歴のためにDBの行や画像は残します。
     */
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

    /**
     * この商品を出品した利用者へのつながりです。
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この商品の会社へのつながりです。
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * この商品が購入された記録へのつながりです。
     *
     * @return HasMany<Sale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * この商品のお気に入り登録へのつながりです。
     *
     * @return HasMany<Like, $this>
     */
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    /**
     * 価格と在庫を、計算や比較に使う整数として扱います。
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['price' => 'integer', 'stock' => 'integer'];
    }
}
