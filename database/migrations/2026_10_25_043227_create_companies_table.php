<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 会社情報を入れる表をDBに作ります。idは会社番号、timestampsは作成・更新日時です。
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->string('company_name');
            $table->timestamps();
        });
    }

    /**
     * この変更を取り消すときに、追加した表や項目を削除します。データも失われるため実行には注意が必要です。
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
