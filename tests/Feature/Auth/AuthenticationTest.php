<?php

namespace Tests\Feature\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_contains_registration_link_and_only_required_login_fields(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee(route('register'), false)
            ->assertDontSee('name="remember"', false)
            ->assertDontSee('/forgot-password', false);
    }

    public function test_login_rotates_session_and_always_redirects_to_products(): void
    {
        $user = User::factory()->unverified()->create();
        $this->withSession(['_token' => 'before-login', 'url.intended' => '/profile']);
        $previous_session_id = session()->getId();

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password', 'remember' => true]);

        $response->assertRedirect(route('products.index'))
            ->assertSessionMissing('url.intended')
            ->assertCookieMissing(Auth::guard('web')->getRecallerName());
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($previous_session_id, session()->getId());
        $this->assertNotSame('before-login', session()->token());
    }

    public function test_invalid_password_returns_error_and_does_not_authenticate(): void
    {
        $user = User::factory()->create();

        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertRedirect('/login')->assertSessionHasErrors(['email' => trans('auth.failed')])
            ->assertSessionHas('_old_input.email', $user->email)
            ->assertSessionMissing('_old_input.password');

        $this->assertGuest();
    }

    public function test_required_login_fields_are_validated(): void
    {
        $this->from('/login')->post('/login', [])->assertRedirect('/login')
            ->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }

    public function test_invalid_email_format_is_rejected(): void
    {
        $this->from('/login')->post('/login', ['email' => 'not-an-email', 'password' => 'password'])
            ->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_sixth_login_attempt_is_blocked_and_login_recovers_after_the_limit_expires(): void
    {
        $user = User::factory()->create();
        $login_request = LoginRequest::create('/login', 'POST', ['email' => $user->email], server: ['REMOTE_ADDR' => '127.0.0.1']);
        $throttle_key = $login_request->throttleKey();
        Event::fake([Lockout::class]);
        $this->freezeTime();

        try {
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
                    ->assertSessionHasErrors('email');
            }

            $this->post('/login', ['email' => $user->email, 'password' => 'password'])
                ->assertSessionHasErrors('email');
            $this->assertGuest();
            Event::assertDispatched(Lockout::class);

            $this->travel(61)->seconds();
            $this->post('/login', ['email' => $user->email, 'password' => 'password'])
                ->assertRedirect(route('products.index'));
            $this->assertAuthenticatedAs($user);
        } finally {
            RateLimiter::clear($throttle_key);
            $this->travelBack();
        }
    }

    public function test_successful_login_clears_previous_failed_attempts(): void
    {
        $user = User::factory()->create();
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('products.index'));
        $this->post('/logout')->assertRedirect(route('login'));
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])->assertSessionHasErrors('email');
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('products.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_logout_invalidates_session_and_redirects_to_login(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['_token' => 'before-logout', 'private_value' => 'private']);
        $previous_session_id = session()->getId();

        $this->post('/logout')->assertRedirect(route('login'))->assertSessionMissing('private_value');

        $this->assertGuest();
        $this->assertNotSame($previous_session_id, session()->getId());
        $this->assertNotSame('before-logout', session()->token());
        $this->get(route('products.index'))->assertRedirect(route('login'));
    }

    public function test_logout_cannot_be_triggered_by_a_get_request(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/logout')->assertMethodNotAllowed();

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_and_logout_require_csrf_tokens_outside_the_test_bypass(): void
    {
        $user = User::factory()->create();
        $this->app->instance('env', 'local');

        try {
            $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertStatus(419);
            $this->assertGuest();
            $this->actingAs($user)->post('/logout')->assertStatus(419);
            $this->assertAuthenticatedAs($user);
        } finally {
            $this->app->instance('env', 'testing');
        }
    }
}
