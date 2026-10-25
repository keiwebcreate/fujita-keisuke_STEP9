<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class AccountEditingTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, string> */
    private function validData(): array
    {
        return ['name' => '更新ユーザー123', 'email' => 'updated@example.test', 'name_kanji' => '佐藤　花子', 'name_kana' => 'サトウ　ハナコ'];
    }

    public function test_guest_cannot_edit_or_update(): void
    {
        $this->get(route('account.edit'))->assertRedirect(route('login'));
        $this->patch(route('account.update'), $this->validData())->assertRedirect(route('login'));
    }

    public function test_current_values_are_displayed_in_specified_order_and_legacy_null_kana_is_empty(): void
    {
        $user = User::factory()->withoutKana()->create();
        $this->actingAs($user)->get(route('account.edit'))->assertOk()->assertSee($user->name)->assertSee($user->email)
            ->assertSeeInOrder(['id="accountName"', 'id="accountEmail"', 'id="accountNameKanji"', 'id="accountNameKana"'], false)
            ->assertDontSee('name="password"', false)->assertDontSee('name="company_id"', false);
    }

    public function test_update_changes_only_authenticated_users_four_fields(): void
    {
        $user = User::factory()->withCompany()->create();
        $other = User::factory()->withCompany()->create();
        $original_password = $user->password;
        $this->actingAs($user)->patch(route('account.update', ['user_id' => $other->id]), $this->validData() + [
            'id' => $other->id, 'user_id' => $other->id, 'company_id' => $other->company_id, 'password' => 'Forged123', 'email_verified_at' => null,
        ])->assertRedirect(route('mypage.show'))->assertSessionHas('status');
        $this->assertSame($this->validData(), $user->fresh()->only(array_keys($this->validData())));
        $this->assertSame($original_password, $user->fresh()->password);
        $this->assertSame($user->company_id, $user->fresh()->company_id);
        $this->assertSame($other->email, $other->fresh()->email);
        $this->get(route('mypage.show'))->assertSee('アカウント情報を更新しました。')->assertSee('ログインユーザー：更新ユーザー123')->assertSee('updated@example.test');
        $this->assertAuthenticatedAs($user);
        $this->get('/account/'.$other->id.'/edit')->assertNotFound();
    }

    public function test_own_email_can_remain_and_other_users_email_is_rejected(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user)->patch(route('account.update'), array_replace($this->validData(), ['email' => $user->email]))->assertSessionHasNoErrors();
        $this->from(route('account.edit'))->patch(route('account.update'), array_replace($this->validData(), ['email' => $other->email]))
            ->assertRedirect(route('account.edit'))->assertSessionHasErrors('email')->assertSessionHasInput('name', '更新ユーザー123');
        $this->assertSame($user->email, $user->fresh()->email);
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_input_is_rejected(string $field, mixed $value): void
    {
        $user = User::factory()->create()->fresh();
        $this->actingAs($user)->from(route('account.edit'))->patch(route('account.update'), array_replace($this->validData(), [$field => $value]))
            ->assertRedirect(route('account.edit'))->assertSessionHasErrors($field);
        $this->assertSame($user->getRawOriginal(), $user->fresh()->getRawOriginal());
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidInputs(): array
    {
        return ['missing name' => ['name', ''], 'long name' => ['name', str_repeat('あ', 256)], 'script name' => ['name', '<script>'],
            'array' => ['name', ['abc']], 'missing email' => ['email', ''], 'bad email' => ['email', 'invalid'], 'uppercase' => ['email', 'UPPER@example.test'],
            'missing kanji' => ['name_kanji', ''], 'ascii kanji' => ['name_kanji', 'Yamada'], 'long kanji' => ['name_kanji', str_repeat('あ', 256)],
            'missing kana' => ['name_kana', ''], 'hiragana kana' => ['name_kana', 'やまだ'], 'halfwidth kana' => ['name_kana', 'ﾔﾏﾀﾞ'], 'long kana' => ['name_kana', str_repeat('ア', 256)]];
    }

    public function test_maximum_length_valid_names_are_accepted(): void
    {
        $this->actingAs(User::factory()->create())->patch(route('account.update'), array_replace($this->validData(), [
            'name' => str_repeat('あ', 255), 'name_kanji' => str_repeat('漢', 255), 'name_kana' => str_repeat('ア', 255),
        ]))->assertSessionHasNoErrors()->assertRedirect(route('mypage.show'));
    }

    public function test_failed_save_rolls_back_and_keeps_safe_input(): void
    {
        $user = User::factory()->create()->fresh();
        $original = $user->getRawOriginal();
        User::updated(function (): void {
            throw new RuntimeException('Simulated update failure');
        });
        try {
            $this->actingAs($user)->patch(route('account.update'), $this->validData() + ['password' => 'Secret123'])
                ->assertRedirect(route('account.edit'))->assertSessionHasErrors('account')->assertSessionHasInput('name', '更新ユーザー123');
            $this->assertSame($original, $user->fresh()->getRawOriginal());
            $this->assertArrayNotHasKey('password', session()->getOldInput());
        } finally {
            User::flushEventListeners();
        }
    }

    public function test_csrf_is_required(): void
    {
        $this->actingAs(User::factory()->create());
        $this->app->instance('env', 'local');
        try {
            $this->patch(route('account.update'), $this->validData())->assertStatus(419);
        } finally {
            $this->app->instance('env', 'testing');
        }
    }
}
