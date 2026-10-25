<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 利用者の表へ漢字名・カナ名・所属会社を追加します。
     * 既存利用者がいる場合は、必須の漢字名を勝手に埋めず、移行方針を決めるため停止します。
     */
    public function up(): void
    {
        if (DB::table('users')->exists()) {
            throw new RuntimeException('既存ユーザーの漢字名の移行方針を決めてから、ユーザー項目の追加を実行してください。');
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->string('name_kanji')->after('name');
            $table->string('name_kana')->nullable()->after('name_kanji');
            $table->foreignId('company_id')->nullable()->after('password')
                ->constrained('companies')->restrictOnDelete();
        });
    }

    /**
     * この変更を取り消すときに、追加した表や項目を削除します。データも失われるため実行には注意が必要です。
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn(['name_kanji', 'name_kana']);
        });
    }
};
