<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_search(): void
    {
        $this->getJson(route('products.index', ['product_name' => 'タブレット']))->assertUnauthorized();
    }

    public function test_and_search_is_inclusive_ordered_and_excludes_own_and_deleted_products(): void
    {
        $user = User::factory()->withCompany()->create();
        Product::factory()->create(['id' => 30, 'product_name' => '大型タブレット', 'price' => 200]);
        Product::factory()->create(['id' => 20, 'product_name' => '小型タブレット', 'price' => 100]);
        Product::factory()->create(['product_name' => '高いタブレット', 'price' => 201]);
        Product::factory()->create(['product_name' => '安いタブレット', 'price' => 99]);
        Product::factory()->create(['product_name' => 'イヤホン', 'price' => 150]);
        Product::factory()->for($user)->create(['product_name' => '自分のタブレット', 'price' => 150]);
        Product::factory()->deleted()->create(['product_name' => '削除したタブレット', 'price' => 150]);

        $response = $this->actingAs($user)->getJson(route('products.index', ['product_name' => 'タブレット', 'min_price' => 100, 'max_price' => 200]));
        $response->assertOk()->assertJsonPath('count', 2)->assertJsonStructure(['html', 'count']);
        $html = $response->json('html');
        $this->assertLessThan(strpos($html, '大型タブレット'), strpos($html, '小型タブレット'));
        foreach (['自分のタブレット', '削除したタブレット', '高いタブレット', '安いタブレット', 'イヤホン'] as $name) {
            $this->assertStringNotContainsString($name, $html);
        }
        $this->assertStringContainsString(route('products.show', 20), $html);
    }

    #[DataProvider('priceFilters')]
    public function test_empty_one_sided_zero_and_maximum_price_filters(array $filters, array $expected): void
    {
        Product::factory()->create(['product_name' => '無料商品', 'price' => 0]);
        Product::factory()->create(['product_name' => '通常商品', 'price' => 100]);
        Product::factory()->create(['product_name' => '最大価格商品', 'price' => 2147483647]);
        $response = $this->actingAs(User::factory()->create())->getJson(route('products.index', $filters));
        $response->assertOk()->assertJsonPath('count', count($expected));
        foreach (['無料商品', '通常商品', '最大価格商品'] as $name) {
            if (in_array($name, $expected, true)) {
                $this->assertStringContainsString($name, $response->json('html'));
            } else {
                $this->assertStringNotContainsString($name, $response->json('html'));
            }
        }
    }

    /** @return array<string, array{array<string, mixed>, array<string>}> */
    public static function priceFilters(): array
    {
        return [
            'empty' => [[], ['無料商品', '通常商品', '最大価格商品']],
            'blank' => [['product_name' => '', 'min_price' => '', 'max_price' => ''], ['無料商品', '通常商品', '最大価格商品']],
            'min only' => [['min_price' => 100], ['通常商品', '最大価格商品']],
            'max only' => [['max_price' => 100], ['無料商品', '通常商品']],
            'zero' => [['min_price' => 0, 'max_price' => 0], ['無料商品']],
            'maximum' => [['min_price' => 2147483647, 'max_price' => 2147483647], ['最大価格商品']],
        ];
    }

    #[DataProvider('invalidFilters')]
    public function test_invalid_filters_return_json_validation_errors(array $filters, string $field): void
    {
        $this->actingAs(User::factory()->create())->getJson(route('products.index', $filters))
            ->assertUnprocessable()->assertJsonValidationErrors($field);
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function invalidFilters(): array
    {
        return [
            'reversed' => [['min_price' => 200, 'max_price' => 100], 'max_price'],
            'negative' => [['min_price' => -1], 'min_price'],
            'decimal' => [['max_price' => '1.5'], 'max_price'],
            'exponent' => [['min_price' => '1e3'], 'min_price'],
            'fullwidth' => [['min_price' => '１００'], 'min_price'],
            'overflow' => [['max_price' => '2147483648'], 'max_price'],
            'text' => [['min_price' => 'abc'], 'min_price'],
            'array price' => [['max_price' => ['1']], 'max_price'],
            'array name' => [['product_name' => ['name']], 'product_name'],
            'long name' => [['product_name' => str_repeat('あ', 256)], 'product_name'],
        ];
    }

    public function test_wildcards_and_escape_character_are_literal_and_sql_is_parameterized(): void
    {
        $this->actingAs(User::factory()->create());
        Product::factory()->create(['product_name' => '100%_!商品']);
        Product::factory()->create(['product_name' => '100ABC商品']);
        foreach (['%', '_', '!'] as $keyword) {
            $response = $this->getJson(route('products.index', ['product_name' => $keyword]));
            $response->assertOk()->assertJsonPath('count', 1);
            $this->assertStringContainsString('100%_!商品', $response->json('html'));
        }
        $this->getJson(route('products.index', ['product_name' => "' OR 1=1 --"]))->assertOk()->assertJsonPath('count', 0);
    }

    public function test_zero_results_and_escaped_content_use_same_list_partial(): void
    {
        $this->actingAs(User::factory()->create());
        Product::factory()->create(['product_name' => '<script>alert(1)</script>', 'description' => '<img src=x onerror=alert(2)>']);
        $response = $this->getJson(route('products.index', ['product_name' => '<script>']));
        $response->assertOk()->assertJsonPath('count', 1);
        $this->assertStringContainsString('&lt;script&gt;', $response->json('html'));
        $this->assertStringNotContainsString('<script>', $response->json('html'));
        $this->assertStringNotContainsString('<img src=x', $response->json('html'));
        $response = $this->getJson(route('products.index', ['product_name' => '存在しない商品']));
        $response->assertOk()->assertJsonPath('count', 0);
        $this->assertStringContainsString('表示できる商品はありません。', $response->json('html'));
    }

    public function test_normal_get_filters_and_preserves_form_values(): void
    {
        Product::factory()->create(['product_name' => 'タブレット', 'price' => 100]);
        Product::factory()->create(['product_name' => 'イヤホン', 'price' => 50]);
        $this->actingAs(User::factory()->create())->get(route('products.index', ['product_name' => 'タブレット', 'min_price' => 100]))
            ->assertOk()->assertSee('value="タブレット"', false)->assertSee('value="100"', false)->assertDontSee('イヤホン');
        $this->from(route('products.index'))->get(route('products.index', ['min_price' => -1]))
            ->assertRedirect(route('products.index'))->assertSessionHasErrors('min_price');
    }
}
