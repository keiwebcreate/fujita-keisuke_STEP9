<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('id="nameKanji"', false);
        $response->assertSee('id="nameKana"', false);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'name_kanji' => '山田太郎',
            'name_kana' => 'ヤマダタロウ',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name_kanji' => '山田太郎',
            'name_kana' => 'ヤマダタロウ',
            'company_id' => null,
        ]);
    }

    #[DataProvider('invalidNames')]
    public function test_invalid_names_are_rejected_without_creating_a_user(string $field, ?string $value): void
    {
        $payload = [
            'name' => 'Test User',
            'name_kanji' => '山田太郎',
            'name_kana' => 'ヤマダタロウ',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];
        $payload[$field] = $value;

        $this->from('/register')->post('/register', $payload)
            ->assertRedirect('/register')
            ->assertSessionHasErrors($field);

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    /** @return array<string, array{string, ?string}> */
    public static function invalidNames(): array
    {
        return [
            'missing kanji' => ['name_kanji', null],
            'half width kanji name' => ['name_kanji', 'Yamada'],
            'too long kanji' => ['name_kanji', str_repeat('山', 256)],
            'missing kana' => ['name_kana', null],
            'half width kana' => ['name_kana', 'ﾔﾏﾀﾞ'],
            'hiragana kana' => ['name_kana', 'やまだ'],
            'too long kana' => ['name_kana', str_repeat('ア', 256)],
        ];
    }

    public function test_registration_does_not_accept_a_company_id_from_the_browser(): void
    {
        $company = Company::factory()->create();

        $this->post('/register', [
            'name' => 'Test User',
            'name_kanji' => '山田　太郎',
            'name_kana' => 'ヤマダ タロウ',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'company_id' => $company->id,
        ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'company_id' => null]);
    }
}
