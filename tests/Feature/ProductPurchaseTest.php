<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ProductPurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_or_purchase(): void
    {
        $this->get(route('purchases.create', 1))->assertRedirect(route('login'));
        $this->post(route('purchases.store', 1), ['quantity' => 1])->assertRedirect(route('login'));
    }

    public function test_purchase_screen_displays_product_company_stock_and_confirmation(): void
    {
        $product = Product::factory()->create(['product_name' => '購入対象商品', 'price' => 1234, 'stock' => 5]);
        $this->actingAs(User::factory()->create())->get(route('purchases.create', $product->id))->assertOk()
            ->assertSee('購入対象商品')->assertSee($product->description)->assertSee($product->company->company_name)
            ->assertSee('¥1,234')->assertSee('value="1"', false)->assertSee('購入内容の確認')->assertSee('キャンセル');
    }

    #[DataProvider('validQuantities')]
    public function test_purchase_creates_sale_and_decrements_stock(int $quantity): void
    {
        $product = Product::factory()->create(['stock' => 5]);
        $buyer = User::factory()->create();
        $this->actingAs($buyer)->post(route('purchases.store', $product->id), [
            'quantity' => $quantity, 'user_id' => $product->user_id, 'product_id' => 999999, 'price' => 0,
        ])->assertRedirect(route('products.index'))->assertSessionHas('status', '商品を購入しました。');
        $this->assertSame(5 - $quantity, $product->fresh()->stock);
        $sale = Sale::sole();
        $this->assertSame($buyer->id, $sale->user_id);
        $this->assertSame($product->id, $sale->product_id);
        $this->assertSame($quantity, $sale->quantity);
        $this->assertNotNull($sale->created_at);
        $this->assertNotNull($sale->updated_at);
        $this->get(route('products.index'))->assertSee('商品を購入しました。');
        $this->get(route('mypage.show'))->assertSee($product->product_name)->assertSee('<td>'.$quantity.'</td>', false);
    }

    /** @return array<string, array{int}> */
    public static function validQuantities(): array
    {
        return ['one' => [1], 'multiple' => [3], 'all stock' => [5]];
    }

    #[DataProvider('invalidQuantities')]
    public function test_invalid_quantity_does_not_change_stock_or_history(mixed $quantity): void
    {
        $product = Product::factory()->create(['stock' => 5]);
        $this->actingAs(User::factory()->create())->from(route('purchases.create', $product->id))
            ->post(route('purchases.store', $product->id), ['quantity' => $quantity])
            ->assertRedirect(route('purchases.create', $product->id))->assertSessionHasErrors('quantity');
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertDatabaseCount('sales', 0);
    }

    /** @return array<string, array{mixed}> */
    public static function invalidQuantities(): array
    {
        return ['missing' => [null], 'zero' => [0], 'negative' => [-1], 'decimal' => ['1.5'], 'exponent' => ['1e2'],
            'fullwidth' => ['１'], 'array' => [[1]], 'text' => ['abc'], 'overflow' => ['2147483648']];
    }

    public function test_insufficient_stock_and_sold_out_are_rejected(): void
    {
        $product = Product::factory()->create(['stock' => 2]);
        $this->actingAs(User::factory()->create())->post(route('purchases.store', $product->id), ['quantity' => 3])
            ->assertRedirect(route('purchases.create', $product->id))->assertSessionHasErrors('quantity')->assertSessionHasInput('quantity', 3);
        $this->assertSame(2, $product->fresh()->stock);
        $product->update(['stock' => 0]);
        $this->get(route('purchases.create', $product->id))->assertOk()->assertSee('売り切れのため購入できません。');
        $this->post(route('purchases.store', $product->id), ['quantity' => 1])->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_own_missing_and_deleted_products_cannot_be_purchased(): void
    {
        $product = Product::factory()->create();
        $this->actingAs($product->user)->get(route('purchases.create', $product->id))->assertForbidden();
        $this->post(route('purchases.store', $product->id), ['quantity' => 1])->assertForbidden();
        $this->actingAs(User::factory()->create());
        $product->delete();
        foreach ([$product->id, 99999999] as $id) {
            $this->get(route('purchases.create', $id))->assertNotFound();
            $this->post(route('purchases.store', $id), ['quantity' => 1])->assertNotFound();
        }
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_model_checks_ownership_and_stock_after_screen_was_loaded(): void
    {
        $product = Product::factory()->create(['stock' => 3]);
        $buyer = User::factory()->create();
        $this->actingAs($buyer)->get(route('purchases.create', $product->id))->assertOk();
        Sale::purchase($buyer->id, (string) $product->id, 2);
        $this->post(route('purchases.store', $product->id), ['quantity' => 2])->assertSessionHasErrors('quantity');
        $this->assertSame(1, $product->fresh()->stock);
        $this->assertDatabaseCount('sales', 1);
    }

    #[DataProvider('failurePoints')]
    public function test_failures_rollback_sale_and_stock(string $failure_point): void
    {
        $product = Product::factory()->create(['stock' => 5]);
        $fail = function (): void {
            throw new RuntimeException('Simulated purchase failure');
        };
        if ($failure_point === 'sale') {
            Sale::created($fail);
        } else {
            Product::updated($fail);
        }
        try {
            $this->actingAs(User::factory()->create())->post(route('purchases.store', $product->id), ['quantity' => 2])
                ->assertRedirect(route('purchases.create', $product->id))->assertSessionHasErrors('purchase');
            $this->assertDatabaseCount('sales', 0);
            $this->assertSame(5, $product->fresh()->stock);
        } finally {
            Sale::flushEventListeners();
            Product::flushEventListeners();
        }
    }

    /** @return array<string, array{string}> */
    public static function failurePoints(): array
    {
        return ['after sale insert' => ['sale'], 'after stock update' => ['stock']];
    }

    public function test_purchase_requires_csrf_outside_test_bypass(): void
    {
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create());
        $this->app->instance('env', 'local');
        try {
            $this->post(route('purchases.store', $product->id), ['quantity' => 1])->assertStatus(419);
        } finally {
            $this->app->instance('env', 'testing');
        }
        $this->assertDatabaseCount('sales', 0);
    }
}
