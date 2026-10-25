<?php

namespace Tests\Feature;

use App\Mail\ContactMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\PendingMail;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, string> */
    private function validData(): array
    {
        return ['name' => '山田太郎', 'email' => 'reply@example.test', 'message' => "商品についての質問です。\n改行した本文です。"];
    }

    public function test_guest_cannot_view_or_send_contact(): void
    {
        Mail::fake();
        $this->get(route('contact.create'))->assertRedirect(route('login'));
        $this->post(route('contact.store'), $this->validData())->assertRedirect(route('login'));
        Mail::assertNothingSent();
    }

    public function test_contact_form_and_footer_link_are_available(): void
    {
        $this->actingAs(User::factory()->create())->get(route('products.index'))->assertSee(route('contact.create'), false);
        $this->get(route('contact.create'))->assertOk()->assertSeeInOrder(['id="contactName"', 'id="contactEmail"', 'id="contactMessage"'], false)
            ->assertSee(route('products.index'), false)->assertSee('送信')->assertSee('戻る');
    }

    public function test_success_sends_to_configured_recipient_with_fixed_from_and_reply_to(): void
    {
        Mail::fake();
        config(['mail.contact_to' => 'admin@example.test', 'mail.from.address' => 'app@example.test', 'mail.from.name' => 'Cytech EC']);
        $this->actingAs(User::factory()->create())->post(route('contact.store'), $this->validData() + ['to' => 'forged@example.test', 'from' => 'forged@example.test'])
            ->assertRedirect(route('products.index'))->assertSessionHas('status', 'お問い合わせを送信しました。');
        Mail::assertSent(ContactMail::class, function (ContactMail $mail): bool {
            return $mail->hasTo('admin@example.test') && $mail->hasFrom('app@example.test') && $mail->hasReplyTo('reply@example.test')
                && $mail->contact_name === '山田太郎' && $mail->contact_body === $this->validData()['message'];
        });
        Mail::assertSentCount(1);
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_inputs_are_rejected_without_sending(string $field, mixed $value): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create())->from(route('contact.create'))
            ->post(route('contact.store'), array_replace($this->validData(), [$field => $value]))
            ->assertRedirect(route('contact.create'))->assertSessionHasErrors($field);
        Mail::assertNothingSent();
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidInputs(): array
    {
        return ['missing name' => ['name', ''], 'name array' => ['name', ['name']], 'long name' => ['name', str_repeat('あ', 256)],
            'missing email' => ['email', ''], 'email format' => ['email', 'invalid'], 'long email' => ['email', str_repeat('a', 250).'@example.test'],
            'header injection' => ['email', "reply@example.test\r\nBcc: victim@example.test"],
            'missing message' => ['message', ''], 'message array' => ['message', ['message']], 'long message' => ['message', str_repeat('あ', 2001)]];
    }

    public function test_maximum_lengths_are_accepted(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create())->post(route('contact.store'), array_replace($this->validData(), [
            'name' => str_repeat('あ', 255), 'message' => str_repeat('あ', 2000),
        ]))->assertSessionHasNoErrors()->assertRedirect(route('products.index'));
        Mail::assertSentCount(1);
    }

    public function test_mail_content_escapes_user_html_and_keeps_line_breaks(): void
    {
        $mail = new ContactMail('<script>alert(1)</script>', 'reply@example.test', "<img src=x onerror=alert(2)>\n次の行");
        $html = $mail->render();
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString("\n次の行", $html);
    }

    public function test_send_failure_returns_form_with_safe_input(): void
    {
        $pending = \Mockery::mock(PendingMail::class);
        $pending->shouldReceive('send')->once()->andThrow(new RuntimeException('Simulated SMTP failure'));
        Mail::shouldReceive('to')->once()->andReturn($pending);
        $this->actingAs(User::factory()->create())->followingRedirects()
            ->post(route('contact.store'), $this->validData() + ['password' => 'Secret123'])
            ->assertOk()->assertSee('お問い合わせを送信できませんでした。時間をおいて再度お試しください。')
            ->assertSee('山田太郎')->assertSee('reply@example.test')->assertDontSee('Secret123');
    }

    public function test_csrf_is_required(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create());
        $this->app->instance('env', 'local');
        try {
            $this->post(route('contact.store'), $this->validData())->assertStatus(419);
        } finally {
            $this->app->instance('env', 'testing');
        }
        Mail::assertNothingSent();
    }
}
