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
     * パスワードはhashedの指定で、元に戻せない形に変換して保存します。
     *
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

    /**
     * 利用者の所属会社へのつながりです。会社未設定の人もいます。
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * この人が出品した商品へのつながりです。
     *
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * この人が購入した記録へのつながりです。
     *
     * @return HasMany<Sale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * この人のお気に入り登録へのつながりです。
     *
     * @return HasMany<Like, $this>
     */
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    /**
     * マイページで使う出品商品と購入履歴を、関連商品も含めてまとめて読み込みます。
     * 先に読み込むことで、表の1行ごとに何度もDBへ問い合わせることを避けます。
     */
    public function getMyPageData(): self
    {
        return $this->load([
            'products' => fn (HasMany $relation): HasMany => $relation->orderBy('id'),
            'sales' => fn (HasMany $relation): HasMany => $relation->with('product')
                ->orderBy('created_at')->orderBy('id'),
        ]);
    }

    /**
     * 本人が変更できる4項目だけ保存します。会社やパスワードはここでは変更しません。
     *
     * @param  array<string, mixed>  $validated_data
     */
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

    /**
     * 入力チェックを通過した利用者情報をDBに登録します。
     *
     * @param  array<string, mixed>  $validated_data
     */
    public static function registerUser(array $validated_data): self
    {
        return self::create($validated_data);
    }
}
