<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 商品情報の表を作ります。foreignIdは、出品者や会社の番号を別の表と結び付けます。
     * restrictOnDeleteは参照先の削除を防ぎ、softDeletesは商品を消さず削除日時で管理する準備です。
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('product_name');
            $table->integer('price');
            $table->integer('stock');
            $table->string('description');
            $table->string('img_path');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * この変更を取り消すときに、追加した表や項目を削除します。データも失われるため実行には注意が必要です。
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
