<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn(['name_kanji', 'name_kana']);
        });
    }
};
