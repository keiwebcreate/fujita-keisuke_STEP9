<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MyPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_my_page(): void
    {
        $this->get(route('mypage.show'))->assertRedirect(route('login'));
    }

    public function test_my_page_displays_the_authenticated_users_four_fields(): void
    {
        $user = User::factory()->create([
            'name' => 'マイページ利用者', 'email' => 'mypage@example.test',
            'name_kanji' => '山田太郎', 'name_kana' => 'ヤマダタロウ',
        ]);

        $this->actingAs($user)->get(route('mypage.show'))->assertOk()
            ->assertSee('マイページ利用者')->assertSee('mypage@example.test')
            ->assertSee('山田太郎')->assertSee('ヤマダタロウ')
            ->assertSee(route('account.edit'), false)->assertSee(route('products.create'), false);
    }

    public function test_empty_lists_and_legacy_null_kana_are_displayed_without_a_company(): void
    {
        $user = User::factory()->withoutKana()->create();

        $this->actingAs($user)->get(route('mypage.show'))->assertOk()->assertSee('未設定')
            ->assertSee('出品した商品はありません。')->assertSee('購入した商品はありません。');
    }

    public function test_only_own_active_listings_are_shown_in_product_number_order(): void
    {
        $user = User::factory()->withCompany()->create();
        Product::factory()->for($user)->create(['id' => 30, 'product_name' => '後の出品']);
        Product::factory()->for($user)->create(['id' => 20, 'product_name' => '先の出品']);
        Product::factory()->for($user)->deleted()->create(['product_name' => '削除した出品']);
        Product::factory()->create(['product_name' => '他人の未購入商品']);

        $response = $this->actingAs($user)->get(route('mypage.show'));

        $response->assertOk()->assertSeeInOrder(['先の出品', '後の出品'])
            ->assertDontSee('削除した出品')->assertDontSee('他人の未購入商品');
        $this->assertSame([20, 30], $response->viewData('user')->products->modelKeys());
    }

    public function test_purchase_history_is_ordered_by_purchase_date_then_sale_id(): void
    {
        $user = User::factory()->create();
        Sale::factory()->for($user)->for(Product::factory()->state(['product_name' => '後の購入']))
            ->create(['id' => 20, 'created_at' => '2026-02-02 00:00:00']);
        Sale::factory()->for($user)->for(Product::factory()->state(['product_name' => '同日後の購入']))
            ->create(['id' => 30, 'created_at' => '2026-01-01 00:00:00']);
        Sale::factory()->for($user)->for(Product::factory()->state(['product_name' => '先の購入']))
            ->create(['id' => 10, 'created_at' => '2026-01-01 00:00:00']);

        $response = $this->actingAs($user)->get(route('mypage.show'));

        $response->assertOk()->assertSeeInOrder(['先の購入', '同日後の購入', '後の購入']);
        $this->assertSame([10, 30, 20], $response->viewData('user')->sales->modelKeys());
    }

    public function test_other_users_information_and_purchases_cannot_be_requested_via_parameters(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create(['name' => '他人のユーザー名', 'email' => 'other-private@example.test']);
        $own_sale = Sale::factory()->for($viewer)->create();
        Sale::factory()->for($other)->for(Product::factory()->state(['product_name' => '他人の購入商品']))->create();

        $response = $this->actingAs($viewer)->get(route('mypage.show', ['user_id' => $other->id]));

        $response->assertOk()->assertDontSee('他人のユーザー名')->assertDontSee('other-private@example.test')
            ->assertDontSee('他人の購入商品');
        $this->assertTrue($response->viewData('user')->is($viewer));
        $this->assertSame([$own_sale->id], $response->viewData('user')->sales->modelKeys());
        $this->get('/mypage/'.$other->id)->assertNotFound();
    }

    public function test_updated_and_soft_deleted_products_remain_in_purchase_history_with_current_values(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['product_name' => '変更前の商品', 'price' => 1000]);
        Sale::factory()->for($user)->for($product)->create(['quantity' => 3]);
        $product->update(['product_name' => '更新後の商品', 'description' => '更新後の説明', 'price' => 123456]);
        $product->delete();

        $response = $this->actingAs($user)->get(route('mypage.show'));

        $response->assertOk()->assertSee('更新後の商品')->assertSee('更新後の説明')
            ->assertSee('¥123,456')->assertSee('<td>3</td>', false)->assertDontSee('変更前の商品');
        $sale = $response->viewData('user')->sales->sole();
        $this->assertSame(3, $sale->quantity);
        $this->assertTrue($sale->product->trashed());
    }

    public function test_user_and_product_content_is_escaped(): void
    {
        $user = User::factory()->withCompany()->create(['name' => '<script>alert(1)</script>', 'name_kanji' => '<img src=x onerror=alert(2)>']);
        $product = Product::factory()->for($user)->create(['product_name' => '<script>alert(3)</script>', 'description' => '<img src=x onerror=alert(4)>']);
        Sale::factory()->for($user)->for($product)->create();

        $response = $this->actingAs($user)->get(route('mypage.show'));

        $response->assertOk();
        foreach ([$user->name, $user->name_kanji, $product->product_name, $product->description] as $value) {
            $response->assertSee($value)->assertDontSee($value, false);
        }
    }

    public function test_purchase_history_is_fetched_with_a_bounded_number_of_queries(): void
    {
        $user = User::factory()->create();
        Sale::factory()->count(6)->for($user)->create();
        $related_queries = [];
        DB::listen(function (QueryExecuted $query) use (&$related_queries): void {
            if (preg_match('/^select .* from `(products|sales)`/i', $query->sql)) {
                $related_queries[] = $query->sql;
            }
        });

        $response = $this->actingAs($user)->get(route('mypage.show'));

        $response->assertOk();
        $this->assertCount(6, $response->viewData('user')->sales);
        $this->assertLessThanOrEqual(3, count($related_queries));
    }

    public function test_header_and_footer_have_working_my_page_links(): void
    {
        $this->actingAs(User::factory()->create())->get(route('products.index'))->assertOk()
            ->assertSee(route('mypage.show'), false)->assertDontSee('マイページ（準備中）');
    }
}
