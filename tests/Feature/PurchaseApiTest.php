<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PurchaseApiTest extends TestCase
{
    use RefreshDatabase;

    private function purchase(Product $product, mixed $quantity, string $token = ''): TestResponse
    {
        Auth::forgetGuards();

        return $this->postJson(route('api.purchases.store', $product->id), ['quantity' => $quantity], ['Authorization' => 'Bearer '.$token]);
    }

    public function test_real_bearer_token_purchases_and_returns_only_contract_fields(): void
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create(['stock' => 5]);
        $token = $buyer->createToken('test', ['purchases:create'], now()->addHour());
        $this->assertNotSame($token->plainTextToken, $token->accessToken->token);
        $this->purchase($product, 2, $token->plainTextToken)->assertCreated()->assertExactJson(['data' => [
            'id' => Sale::sole()->id, 'product_id' => $product->id, 'quantity' => 2, 'remaining_stock' => 3,
        ]]);
        $this->assertSame($buyer->id, Sale::sole()->user_id);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_missing_invalid_expired_and_revoked_tokens_are_unauthorized(): void
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create();
        $expired = $buyer->createToken('expired', ['purchases:create'], now()->subMinute())->plainTextToken;
        $revoked = $buyer->createToken('revoked', ['purchases:create']);
        $revoked->accessToken->delete();
        foreach (['', 'invalid', $expired, $revoked->plainTextToken] as $token) {
            $this->purchase($product, 1, $token)->assertUnauthorized();
        }
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_session_authentication_does_not_replace_bearer_token(): void
    {
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create())->postJson(route('api.purchases.store', $product->id), ['quantity' => 1])->assertUnauthorized();
    }

    public function test_insufficient_ability_and_own_product_are_forbidden(): void
    {
        $product = Product::factory()->create();
        $buyer = User::factory()->create();
        $this->purchase($product, 1, $buyer->createToken('read', ['products:read'])->plainTextToken)->assertForbidden();
        $this->purchase($product, 1, $product->user->createToken('owner', ['purchases:create'])->plainTextToken)->assertForbidden();
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_missing_deleted_and_insufficient_stock_are_reported(): void
    {
        $product = Product::factory()->create(['stock' => 1]);
        $token = User::factory()->create()->createToken('test', ['purchases:create'])->plainTextToken;
        $this->purchase($product, 2, $token)->assertConflict();
        $product->delete();
        $this->purchase($product, 1, $token)->assertNotFound();
        $product->id = 99999999;
        $this->purchase($product, 1, $token)->assertNotFound();
        $this->assertDatabaseCount('sales', 0);
    }

    #[DataProvider('invalidQuantities')]
    public function test_quantity_validation_matches_web(mixed $quantity): void
    {
        $product = Product::factory()->create();
        $token = User::factory()->create()->createToken('test', ['purchases:create'])->plainTextToken;
        $this->purchase($product, $quantity, $token)->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->assertDatabaseCount('sales', 0);
    }

    /** @return array<string, array{mixed}> */
    public static function invalidQuantities(): array
    {
        return ProductPurchaseTest::invalidQuantities();
    }

    public function test_web_and_api_share_stock_history_and_ignore_forged_fields(): void
    {
        $product = Product::factory()->create(['stock' => 5]);
        $buyer = User::factory()->create();
        $token = $buyer->createToken('test', ['purchases:create'])->plainTextToken;
        $this->actingAs($buyer)->post(route('purchases.store', $product->id), ['quantity' => 2])->assertRedirect(route('products.index'));
        Auth::forgetGuards();
        $this->postJson(route('api.purchases.store', $product->id), ['quantity' => 3, 'user_id' => $product->user_id, 'price' => 0, 'product_id' => 999], ['Authorization' => 'Bearer '.$token])
            ->assertCreated()->assertJsonPath('data.remaining_stock', 0);
        $this->assertSame(0, $product->fresh()->stock);
        $this->assertSame(5, (int) Sale::where('user_id', $buyer->id)->sum('quantity'));
        $this->purchase($product, 1, $token)->assertConflict();
        $this->assertDatabaseCount('sales', 2);
    }

    public function test_api_returns_json_without_accept_header(): void
    {
        $this->post('/api/products/1/purchases', ['quantity' => 1])->assertUnauthorized()->assertHeader('Content-Type', 'application/json');
    }
}
