<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductBrowsingTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_excludes_own_and_deleted_products_and_orders_by_product_number(): void
    {
        $viewer = User::factory()->withCompany()->create();
        Product::factory()->for($viewer)->create(['id' => 10, 'product_name' => '自分の商品']);
        Product::factory()->create(['id' => 30, 'product_name' => '後の商品', 'price' => 100]);
        Product::factory()->soldOut()->create(['id' => 20, 'product_name' => '先の商品', 'price' => 200]);
        Product::factory()->deleted()->create(['id' => 40, 'product_name' => '削除済み商品']);

        $response = $this->actingAs($viewer)->get(route('products.index'));

        $response->assertOk()->assertSeeInOrder(['先の商品', '後の商品'])
            ->assertDontSee('自分の商品')->assertDontSee('削除済み商品')
            ->assertSee(route('products.show', 20), false)
            ->assertSee(route('products.show', 30), false);
        $this->assertSame([20, 30], $response->viewData('products')->modelKeys());
    }

    public function test_empty_listing_renders_a_message_when_only_own_or_deleted_products_exist(): void
    {
        $viewer = User::factory()->withCompany()->create();
        Product::factory()->for($viewer)->create();
        Product::factory()->deleted()->create();

        $this->actingAs($viewer)->get(route('products.index'))->assertOk()
            ->assertSee('表示できる商品はありません。')->assertDontSee('<table', false);
    }

    public function test_detail_displays_product_values_and_the_products_company(): void
    {
        $company = Company::factory()->create(['company_name' => '商品に設定された会社']);
        $product = Product::factory()->for($company)->create([
            'product_name' => 'タブレット', 'description' => "持ち運び用です。\n軽量です。", 'price' => 123456,
        ]);
        $viewer = User::factory()->create();

        $response = $this->actingAs($viewer)->get(route('products.show', $product->id));

        $response->assertOk()->assertSee('タブレット')->assertSee('持ち運び用です。')
            ->assertSee('軽量です。')->assertSee('¥123,456')->assertSee('商品に設定された会社')
            ->assertSee(route('products.index'), false)->assertSee('戻る')
            ->assertSee(route('purchases.create', $product->id), false);
    }

    public function test_owners_can_view_their_own_product_detail(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($product->user)->get(route('products.show', $product->id))
            ->assertOk()->assertSee($product->product_name);
    }

    public function test_sold_out_products_remain_viewable(): void
    {
        $product = Product::factory()->soldOut()->create();

        $this->actingAs(User::factory()->create())->get(route('products.show', $product->id))
            ->assertOk()->assertSee('売り切れ');
    }

    public function test_deleted_product_details_return_404(): void
    {
        $product = Product::factory()->deleted()->create();

        $this->actingAs(User::factory()->create())->get(route('products.show', $product->id))->assertNotFound();
    }

    #[DataProvider('invalidProductIds')]
    public function test_invalid_or_missing_product_details_return_404(string $product_id): void
    {
        $this->actingAs(User::factory()->create())->get('/products/'.$product_id)->assertNotFound();
    }

    /** @return array<string, array{string}> */
    public static function invalidProductIds(): array
    {
        return ['missing' => ['99999999'], 'non numeric' => ['missing'], 'negative' => ['-1'], 'large' => ['999999999999999999999999999999']];
    }

    public function test_guests_cannot_access_product_list_or_detail(): void
    {
        $product = Product::factory()->create();

        $this->get(route('products.index'))->assertRedirect(route('login'));
        $this->get(route('products.show', $product->id))->assertRedirect(route('login'));
    }

    public function test_product_and_company_content_is_escaped_on_list_and_detail(): void
    {
        $company = Company::factory()->create(['company_name' => '会社<script>alert(1)</script>']);
        $product = Product::factory()->for($company)->create([
            'product_name' => '商品<script>alert(2)</script>',
            'description' => '<img src=x onerror=alert(3)>',
        ]);
        $viewer = User::factory()->create();

        foreach ([route('products.index'), route('products.show', $product->id)] as $url) {
            $this->actingAs($viewer)->get($url)->assertOk()
                ->assertSee($product->product_name)->assertSee($product->description)
                ->assertDontSee($product->product_name, false)->assertDontSee($product->description, false);
        }
        $this->get(route('products.show', $product->id))->assertSee($company->company_name)
            ->assertDontSee($company->company_name, false);
    }

    public function test_existing_product_images_are_displayed_from_public_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->putFileAs('products', UploadedFile::fake()->image('tablet.png'), 'tablet.png');
        $product = Product::factory()->create(['img_path' => 'products/tablet.png']);
        $this->actingAs(User::factory()->create());

        foreach ([route('products.index'), route('products.show', $product->id)] as $url) {
            $this->get($url)->assertOk()->assertSee('/storage/products/tablet.png', false)
                ->assertDontSee('画像なし');
        }
    }

    #[DataProvider('missingOrUnsafeImages')]
    public function test_missing_or_unsafe_images_render_a_placeholder(string $image_path): void
    {
        Storage::fake('public');
        $product = Product::factory()->create(['img_path' => $image_path]);

        $this->actingAs(User::factory()->create())->get(route('products.show', $product->id))
            ->assertOk()->assertSee('画像なし')->assertDontSee('<img ', false);
    }

    /** @return array<string, array{string}> */
    public static function missingOrUnsafeImages(): array
    {
        return [
            'missing image' => ['products/not-found.png'],
            'remote image' => ['https://example.test/track.png'],
            'parent path' => ['products/../private/image.png'],
            'script attribute' => ['products/image.png" onerror="alert(1)'],
        ];
    }

    public function test_common_header_footer_and_disabled_future_links_are_present(): void
    {
        $viewer = User::factory()->create(['name' => '閲覧ユーザー']);

        $this->actingAs($viewer)->get(route('products.index'))->assertOk()
            ->assertSee('ログインユーザー：閲覧ユーザー')->assertSee('ログアウト')
            ->assertSee('お問い合わせ（準備中）')->assertSee(route('mypage.show'), false)
            ->assertSee('<footer', false)->assertSee('© 2024 Company, Inc')
            ->assertDontSee('href="#"', false);
    }
}
