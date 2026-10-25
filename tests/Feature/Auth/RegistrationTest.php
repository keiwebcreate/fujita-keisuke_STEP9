<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name_kanji' => '山田太郎',
            'name_kana' => 'ヤマダタロウ',
            'company_id' => null,
        ]);
        $this->assertTrue(Hash::check('password', User::where('email', 'test@example.com')->firstOrFail()->password));
        $response->assertSessionHas('status', '登録が完了しました。ログインしてください。');
    }

    public function test_password_mismatch_and_duplicate_email_do_not_create_new_users(): void
    {
        $user = User::factory()->create(['email' => 'taken@example.com']);
        $payload = [
            'name' => 'Test User', 'name_kanji' => '山田太郎', 'name_kana' => 'ヤマダタロウ',
            'email' => 'taken@example.com', 'password' => 'password', 'password_confirmation' => 'different',
        ];

        $this->from('/register')->post('/register', $payload)->assertRedirect('/register')
            ->assertSessionHasErrors(['email', 'password'])
            ->assertSessionHas('_old_input.name_kanji', '山田太郎')
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('_old_input.password_confirmation');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
        $this->assertModelExists($user);
    }

    public function test_password_boundary_lengths_are_accepted(): void
    {
        foreach ([8, 72] as $length) {
            $this->post('/register', [
                'name' => 'Test User', 'name_kanji' => '山田太郎', 'name_kana' => 'ヤマダタロウ',
                'email' => 'boundary'.$length.'@example.com',
                'password' => str_repeat('A', $length), 'password_confirmation' => str_repeat('A', $length),
            ])->assertSessionHasNoErrors()->assertRedirect(route('login'));
            $this->assertGuest();
        }

        $this->assertDatabaseCount('users', 2);
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
        if ($field === 'password') {
            $payload['password_confirmation'] = $value;
        }

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
            'missing user name' => ['name', null],
            'half width kana user name' => ['name', 'ﾔﾏﾀﾞ'],
            'too long user name' => ['name', str_repeat('A', 256)],
            'missing password' => ['password', null],
            'short password' => ['password', 'short'],
            'too long password' => ['password', str_repeat('A', 73)],
            'password punctuation' => ['password', 'password!'],
            'full width password' => ['password', 'ＡＢＣＤＥＦＧＨ'],
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
        ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'company_id' => null]);
    }
}
