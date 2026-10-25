<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ProductListingTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function validData(): array
    {
        return ['product_name' => 'タブレット ABC123', 'description' => "軽量です。\n持ち運び用", 'price' => '1000', 'stock' => '3', 'image' => UploadedFile::fake()->image('tablet.png')];
    }

    public function test_guests_cannot_create_store_or_view_listing_details(): void
    {
        $this->get(route('products.create'))->assertRedirect(route('login'));
        $this->post(route('products.store'))->assertRedirect(route('login'));
        $this->get(route('listed_products.show', 1))->assertRedirect(route('login'));
    }

    public function test_registration_uses_authenticated_owner_and_company_and_stores_public_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->withCompany()->create();
        $other = User::factory()->withCompany()->create();
        $data = $this->validData() + ['user_id' => $other->id, 'company_id' => $other->company_id, 'img_path' => 'forged.png', 'deleted_at' => now()];

        $this->actingAs($user)->get(route('products.create'))->assertOk()->assertSee('multipart/form-data', false);
        $this->post(route('products.store'), $data)->assertRedirect(route('mypage.show'))->assertSessionHas('status');
        $product = Product::sole();
        $this->assertSame($user->id, $product->user_id);
        $this->assertSame($user->company_id, $product->company_id);
        $this->assertSame(1000, $product->price);
        $this->assertSame(3, $product->stock);
        $this->assertFalse($product->trashed());
        $this->assertMatchesRegularExpression('/\Aproducts\/[A-Za-z0-9]+\.png\z/', $product->img_path);
        Storage::disk('public')->assertExists($product->img_path);
        $this->get(route('mypage.show'))->assertSee('商品を登録しました。')->assertSee(route('listed_products.show', $product->id), false);
        $this->get(route('listed_products.show', $product->id))->assertOk()->assertSee('タブレット ABC123')->assertSee('/storage/'.$product->img_path, false)->assertSee('編集（準備中）')->assertSee('削除する（準備中）');
    }

    public function test_user_without_company_is_informed_and_cannot_register(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create())->get(route('products.create'))->assertOk()->assertSee('所属会社が未設定');
        $this->post(route('products.store'), $this->validData())->assertSessionHasErrors('company');
        $this->assertDatabaseCount('products', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_inputs_are_rejected_without_saving(string $field, mixed $value): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->withCompany()->create());
        $data = $this->validData();
        $data[$field] = $value;
        $this->from(route('products.create'))->post(route('products.store'), $data)
            ->assertRedirect(route('products.create'))->assertSessionHasErrors($field);
        $this->assertDatabaseCount('products', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidInputs(): array
    {
        return [
            'missing name' => ['product_name', ''], 'long name' => ['product_name', str_repeat('あ', 256)],
            'invalid name' => ['product_name', '<script>'], 'missing description' => ['description', ''],
            'long description' => ['description', str_repeat('あ', 256)],
            'negative price' => ['price', '-1'], 'decimal price' => ['price', '1.5'],
            'overflow price' => ['price', '2147483648'], 'missing price' => ['price', ''],
            'fullwidth price' => ['price', '１００'], 'exponent' => ['price', '1e3'],
            'negative stock' => ['stock', '-1'], 'decimal stock' => ['stock', '1.2'],
            'overflow stock' => ['stock', '2147483648'], 'missing stock' => ['stock', ''],
            'missing image' => ['image', null],
        ];
    }

    public function test_invalid_and_oversized_files_are_rejected(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->withCompany()->create());
        foreach ([UploadedFile::fake()->create('fake.jpg', 20, 'text/plain'), UploadedFile::fake()->image('image.gif'), UploadedFile::fake()->image('large.png')->size(2049)] as $image) {
            $this->post(route('products.store'), array_replace($this->validData(), ['image' => $image]))->assertSessionHasErrors('image');
        }
        $this->assertDatabaseCount('products', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_zero_and_maximum_integer_values_and_255_character_text_are_accepted(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->withCompany()->create());
        foreach ([0, 2147483647] as $value) {
            $this->post(route('products.store'), array_replace($this->validData(), ['price' => $value, 'stock' => $value, 'product_name' => str_repeat('あ', 255), 'description' => str_repeat('い', 255)]))->assertSessionHasNoErrors()->assertRedirect(route('mypage.show'));
        }
        $this->assertDatabaseCount('products', 2);
    }

    public function test_database_failure_removes_uploaded_image_and_keeps_safe_input(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->withCompany()->create());
        Product::creating(function (): void {
            throw new RuntimeException('Simulated database failure');
        });
        try {
            $this->from(route('products.create'))->post(route('products.store'), $this->validData())
                ->assertRedirect(route('products.create'))->assertSessionHasErrors('registration')
                ->assertSessionHasInput('product_name', 'タブレット ABC123');
            $this->assertDatabaseCount('products', 0);
            $this->assertSame([], Storage::disk('public')->allFiles());
        } finally {
            Product::flushEventListeners();
        }
    }

    public function test_jpeg_png_and_webp_images_are_accepted(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->withCompany()->create());
        foreach (['jpg', 'png', 'webp'] as $extension) {
            $this->post(route('products.store'), array_replace($this->validData(), [
                'image' => UploadedFile::fake()->image('upload.'.$extension)->size(2048),
            ]))->assertSessionHasNoErrors()->assertRedirect(route('mypage.show'));
        }
        $this->assertDatabaseCount('products', 3);
        $this->assertCount(3, Storage::disk('public')->allFiles());
    }

    public function test_image_write_failure_does_not_create_a_product(): void
    {
        $this->actingAs(User::factory()->withCompany()->create());
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('public')->once()->andReturn($disk);

        $this->from(route('products.create'))->post(route('products.store'), $this->validData())
            ->assertRedirect(route('products.create'))->assertSessionHasErrors('registration');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_listing_details_reject_other_owners_and_missing_or_deleted_products(): void
    {
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create())->get(route('listed_products.show', $product->id))->assertForbidden();
        $this->actingAs($product->user)->get(route('listed_products.show', $product->id))->assertOk();
        $product->delete();
        $this->get(route('listed_products.show', $product->id))->assertNotFound();
        $this->get(route('listed_products.show', 99999999))->assertNotFound();
    }
}
