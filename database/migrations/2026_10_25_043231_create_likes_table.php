<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * お気に入りの表を作ります。利用者番号と商品番号の組合せをuniqueにして二重登録を防ぎます。
     */
    public function up(): void
    {
        Schema::create('likes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });
    }

    /**
     * この変更を取り消すときに、追加した表や項目を削除します。データも失われるため実行には注意が必要です。
     */
    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};
