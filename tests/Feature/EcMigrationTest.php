<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class EcMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh')->assertSuccessful();
    }

    protected function tearDown(): void
    {
        try {
            $this->artisan('migrate:fresh')->assertSuccessful();
        } finally {
            parent::tearDown();
        }
    }

    public function test_ec_migrations_can_be_rolled_back_and_reapplied_without_recreating_the_users_table(): void
    {
        $this->artisan('migrate:rollback', ['--step' => 5])->assertSuccessful();

        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('sessions'));
        $this->assertFalse(Schema::hasColumn('users', 'name_kanji'));
        foreach (['companies', 'products', 'sales', 'likes'] as $table_name) {
            $this->assertFalse(Schema::hasTable($table_name));
        }

        $this->artisan('migrate')->assertSuccessful();

        $this->assertTrue(Schema::hasColumn('users', 'name_kanji'));
        $this->assertTrue(Schema::hasColumn('products', 'deleted_at'));
        $this->assertTrue(Schema::hasTable('sales'));
        $this->assertTrue(Schema::hasTable('likes'));
    }

    public function test_profile_migration_refuses_to_invent_names_for_existing_users(): void
    {
        $this->artisan('migrate:rollback', ['--step' => 5])->assertSuccessful();
        $user = User::registerUser(['name' => 'Existing user', 'email' => 'existing@example.test', 'password' => 'password']);
        $migration_path = glob(database_path('migrations/*_add_profile_fields_to_users_table.php'))[0];
        $migration = require $migration_path;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('既存ユーザーの漢字名の移行方針');

        try {
            $migration->up();
        } finally {
            $this->assertModelExists($user);
            $this->assertFalse(Schema::hasColumn('users', 'name_kanji'));
        }
    }
}
