<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 購入者・商品・数量を記録する表を作ります。
     * indexは購入履歴を利用者と日付で探しやすくする、索引のような仕組みです。
     */
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->bigInteger('quantity');
            $table->timestamps();
            $table->index(['user_id', 'created_at', 'id']);
        });
    }

    /**
     * この変更を取り消すときに、追加した表や項目を削除します。データも失われるため実行には注意が必要です。
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
