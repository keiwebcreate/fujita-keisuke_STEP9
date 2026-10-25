<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Like;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EcDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_related_company_seller_buyer_sales_and_likes_are_kept_separate(): void
    {
        $company = Company::factory()->create();
        $seller = User::factory()->for($company)->create();
        $buyer = User::factory()->create();
        $product = Product::factory()->for($seller)->create();
        $sale = Sale::factory()->for($buyer)->for($product)->create();
        $like = Like::factory()->for($buyer)->for($product)->create();

        $this->assertTrue($product->user->is($seller));
        $this->assertTrue($product->company->is($company));
        $this->assertTrue($seller->company->is($company));
        $this->assertTrue($company->users->sole()->is($seller));
        $this->assertTrue($company->products->sole()->is($product));
        $this->assertTrue($seller->products->sole()->is($product));
        $this->assertTrue($buyer->sales->sole()->is($sale));
        $this->assertTrue($buyer->likes->sole()->is($like));
        $this->assertTrue($sale->user->is($buyer));
        $this->assertTrue($like->user->is($buyer));
        $this->assertTrue($product->sales->sole()->is($sale));
        $this->assertTrue($product->likes->sole()->is($like));
        $this->assertCount(0, $buyer->products);
        $this->assertCount(0, $seller->sales);
    }

    public function test_soft_deleted_products_are_hidden_but_remain_available_in_purchase_history(): void
    {
        $product = Product::factory()->create();
        $sale = Sale::factory()->for($product)->create();
        $like = Like::factory()->for($product)->create();

        $product->delete();

        $this->assertSoftDeleted($product);
        $this->assertNull(Product::find($product->id));
        $this->assertCount(0, $product->user->products);
        $this->assertCount(0, $product->company->products);
        $this->assertTrue($sale->fresh()->product->is($product));
        $this->assertModelExists($sale);
        $this->assertModelExists($like);
        $this->assertNull($like->fresh()->product);
    }

    public function test_purchase_history_uses_current_product_information(): void
    {
        $product = Product::factory()->create(['price' => 1000]);
        $sale = Sale::factory()->for($product)->create(['quantity' => 2]);

        $product->update(['product_name' => '更新後の商品', 'description' => '更新後の説明', 'price' => 2000]);

        $history_product = $sale->fresh()->product;
        $this->assertSame('更新後の商品', $history_product->product_name);
        $this->assertSame('更新後の説明', $history_product->description);
        $this->assertSame(2000, $history_product->price);
        $this->assertSame(2, $sale->fresh()->quantity);
    }

    public function test_nullable_user_fields_and_sold_out_factory_are_supported(): void
    {
        $user = User::factory()->withoutKana()->create();
        $product = Product::factory()->soldOut()->create();

        $this->assertNull($user->fresh()->company_id);
        $this->assertNull($user->fresh()->name_kana);
        $this->assertSame(0, $product->fresh()->stock);
        $this->assertSame($product->user->company_id, $product->company_id);
    }

    public function test_favorite_registration_cannot_be_duplicated_for_the_same_user_and_product(): void
    {
        $like = Like::factory()->create();

        $this->expectException(QueryException::class);
        $this->expectExceptionCode('23000');

        Like::factory()->for($like->user)->for($like->product)->create();
    }

    #[DataProvider('foreignKeyCases')]
    public function test_records_cannot_reference_a_missing_parent(string $model, string $field): void
    {
        $record = $model::factory()->create();

        $this->expectException(QueryException::class);
        $this->expectExceptionCode('23000');

        $record->forceFill([$field => 999999999])->save();
    }

    /** @return array<string, array{class-string, string}> */
    public static function foreignKeyCases(): array
    {
        return [
            'user company' => [User::class, 'company_id'],
            'product seller' => [Product::class, 'user_id'],
            'product company' => [Product::class, 'company_id'],
            'sale buyer' => [Sale::class, 'user_id'],
            'sale product' => [Sale::class, 'product_id'],
            'like user' => [Like::class, 'user_id'],
            'like product' => [Like::class, 'product_id'],
        ];
    }

    #[DataProvider('protectedParents')]
    public function test_physical_deletion_cannot_remove_referenced_data(string $parent): void
    {
        $sale = Sale::factory()->create();
        $product = $sale->product;
        $target = match ($parent) {
            'product' => $product,
            'seller' => $product->user,
            'buyer' => $sale->user,
            'company' => $product->company,
        };

        $this->expectException(QueryException::class);
        $this->expectExceptionCode('23000');

        if ($parent === 'product') {
            $target->forceDelete();
        } else {
            $target->delete();
        }
    }

    /** @return array<string, array{string}> */
    public static function protectedParents(): array
    {
        return ['product' => ['product'], 'seller' => ['seller'], 'buyer' => ['buyer'], 'company' => ['company']];
    }
}
