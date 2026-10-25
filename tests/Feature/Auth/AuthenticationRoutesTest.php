<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticationRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_before_viewing_products(): void
    {
        $this->get(route('products.index'))->assertRedirect(route('login'));
        $this->get('/')->assertRedirect(route('login'));
        $this->post('/logout')->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_products_without_email_verification_or_company_assignment(): void
    {
        $user = User::factory()->unverified()->create(['name' => '表示名<script>alert(1)</script>']);

        $this->actingAs($user)->get(route('products.index'))->assertOk()
            ->assertSee('商品一覧')->assertSee('準備中')
            ->assertSee($user->name)->assertDontSee($user->name, false);
    }

    public function test_authenticated_users_are_redirected_from_guest_screens_and_root_to_products(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['/', '/login', '/register'] as $path) {
            $this->get($path)->assertRedirect(route('products.index'));
        }
    }

    #[DataProvider('removedRoutes')]
    public function test_unused_breeze_routes_are_unavailable_to_guests_and_logged_in_users(string $method, string $path): void
    {
        $this->call($method, $path)->assertNotFound();
        $this->actingAs(User::factory()->create())->call($method, $path)->assertNotFound();
    }

    /** @return array<string, array{string, string}> */
    public static function removedRoutes(): array
    {
        return [
            'dashboard' => ['GET', '/dashboard'],
            'profile view' => ['GET', '/profile'],
            'profile update' => ['PATCH', '/profile'],
            'account deletion' => ['DELETE', '/profile'],
            'password update' => ['PUT', '/password'],
            'password reset form' => ['GET', '/forgot-password'],
            'password reset request' => ['POST', '/forgot-password'],
            'password reset token' => ['GET', '/reset-password/token'],
            'password reset' => ['POST', '/reset-password'],
            'password confirmation form' => ['GET', '/confirm-password'],
            'password confirmation' => ['POST', '/confirm-password'],
            'email verification form' => ['GET', '/verify-email'],
            'email verification' => ['GET', '/verify-email/1/hash'],
            'email notification' => ['POST', '/email/verification-notification'],
        ];
    }
}
