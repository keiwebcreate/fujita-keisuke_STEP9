<?php

namespace Tests\Feature;

use App\Models\Like;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private function productWithImage(): Product
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/original.png', 'original image');

        return Product::factory()->create(['img_path' => 'products/original.png'])->fresh();
    }

    /** @return array<string, mixed> */
    private function validData(): array
    {
        return ['product_name' => '更新したタブレット', 'description' => "新しい説明です。\n２行目", 'price' => '2500', 'stock' => '5'];
    }

    public function test_guest_and_other_user_cannot_edit_update_or_delete(): void
    {
        $product = $this->productWithImage();
        foreach ([null, User::factory()->create()] as $user) {
            if ($user !== null) {
                $this->actingAs($user);
            }
            $responses = [
                $this->get(route('listed_products.edit', $product->id)),
                $this->patch(route('listed_products.update', $product->id), $this->validData()),
                $this->delete(route('listed_products.destroy', $product->id)),
            ];
            foreach ($responses as $response) {
                if ($user === null) {
                    $response->assertRedirect(route('login'));
                } else {
                    $response->assertForbidden();
                }
            }
        }
        $this->assertFalse($product->fresh()->trashed());
        $this->assertNotSame('更新したタブレット', $product->fresh()->product_name);
        Storage::disk('public')->assertExists($product->img_path);
    }

    public function test_edit_displays_current_values_and_image(): void
    {
        $product = $this->productWithImage();
        $this->actingAs($product->user)->get(route('listed_products.edit', $product->id))
            ->assertOk()->assertSee($product->product_name)->assertSee($product->description)
            ->assertSee('value="'.$product->price.'"', false)->assertSee('value="'.$product->stock.'"', false)
            ->assertSee('/storage/products/original.png', false)->assertSee('未選択なら現在の画像を保持します。');
    }

    public function test_update_without_image_preserves_image_owner_and_company(): void
    {
        $product = $this->productWithImage();
        $other = User::factory()->withCompany()->create();
        $this->actingAs($product->user)->patch(route('listed_products.update', $product->id), $this->validData() + [
            'user_id' => $other->id, 'company_id' => $other->company_id, 'img_path' => 'products/forged.png', 'deleted_at' => now(),
        ])->assertSessionHasNoErrors()->assertRedirect(route('listed_products.show', $product->id));
        $fresh = $product->fresh();
        $this->assertSame('更新したタブレット', $fresh->product_name);
        $this->assertSame(2500, $fresh->price);
        $this->assertSame(5, $fresh->stock);
        $this->assertSame($product->user_id, $fresh->user_id);
        $this->assertSame($product->company_id, $fresh->company_id);
        $this->assertSame($product->img_path, $fresh->img_path);
        $this->assertFalse($fresh->trashed());
        Storage::disk('public')->assertExists($fresh->img_path);
        $this->get(route('listed_products.show', $product->id))->assertSee('商品を更新しました。');
    }

    public function test_existing_owner_without_current_company_can_update_existing_listing(): void
    {
        $product = $this->productWithImage();
        $product->user->forceFill(['company_id' => null])->save();
        $this->actingAs($product->user->fresh())->patch(route('listed_products.update', $product->id), $this->validData())
            ->assertSessionHasNoErrors()->assertRedirect(route('listed_products.show', $product->id));
        $this->assertSame($product->company_id, $product->fresh()->company_id);
    }

    public function test_replacement_image_is_saved_and_old_image_removed_after_update(): void
    {
        $product = $this->productWithImage();
        $this->actingAs($product->user)->patch(route('listed_products.update', $product->id), $this->validData() + [
            'image' => UploadedFile::fake()->image('replacement.webp'),
        ])->assertSessionHasNoErrors()->assertRedirect(route('listed_products.show', $product->id));
        $fresh = $product->fresh();
        $this->assertNotSame($product->img_path, $fresh->img_path);
        Storage::disk('public')->assertExists($fresh->img_path);
        Storage::disk('public')->assertMissing($product->img_path);
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_update_preserves_existing_data_and_image(string $field, mixed $value): void
    {
        $product = $this->productWithImage();
        $this->actingAs($product->user)->from(route('listed_products.edit', $product->id))
            ->patch(route('listed_products.update', $product->id), array_replace($this->validData(), [$field => $value]))
            ->assertRedirect(route('listed_products.edit', $product->id))->assertSessionHasErrors($field);
        $this->assertSame($product->getRawOriginal(), $product->fresh()->getRawOriginal());
        $this->assertSame(['products/original.png'], Storage::disk('public')->allFiles());
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidInputs(): array
    {
        return array_diff_key(ProductListingTest::invalidInputs(), ['missing image' => true]);
    }

    public function test_invalid_and_oversized_replacement_images_preserve_old_image(): void
    {
        $product = $this->productWithImage();
        $this->actingAs($product->user);
        foreach ([UploadedFile::fake()->create('fake.jpg', 10, 'text/plain'), UploadedFile::fake()->image('bad.gif'), UploadedFile::fake()->image('large.png')->size(2049)] as $image) {
            $this->patch(route('listed_products.update', $product->id), $this->validData() + ['image' => $image])->assertSessionHasErrors('image');
        }
        $this->assertSame($product->getRawOriginal(), $product->fresh()->getRawOriginal());
        $this->assertSame(['products/original.png'], Storage::disk('public')->allFiles());
    }

    public function test_database_update_failure_removes_new_image_and_keeps_old_data(): void
    {
        $product = $this->productWithImage();
        Product::updated(function (): void {
            throw new RuntimeException('Simulated update failure after SQL');
        });
        try {
            $this->actingAs($product->user)->patch(route('listed_products.update', $product->id), $this->validData() + [
                'image' => UploadedFile::fake()->image('new.png'),
            ])->assertSessionHasErrors('update')->assertSessionHasInput('product_name', '更新したタブレット');
            $this->assertSame($product->getRawOriginal(), $product->fresh()->getRawOriginal());
            $this->assertSame(['products/original.png'], Storage::disk('public')->allFiles());
        } finally {
            Product::flushEventListeners();
        }
    }

    public function test_image_write_failure_does_not_update_product(): void
    {
        $product = $this->productWithImage();
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('public')->once()->andReturn($disk);
        $this->actingAs($product->user)->patch(route('listed_products.update', $product->id), $this->validData() + [
            'image' => UploadedFile::fake()->image('new.png'),
        ])->assertSessionHasErrors('update');
        $this->assertSame($product->getRawOriginal(), $product->fresh()->getRawOriginal());
    }

    public function test_old_image_cleanup_failure_does_not_undo_successful_update(): void
    {
        $product = $this->productWithImage();
        $disk = Storage::disk('public');
        $mock_disk = \Mockery::mock($disk)->makePartial();
        $mock_disk->shouldReceive('delete')->with('products/original.png')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('public')->andReturn($mock_disk);
        $this->actingAs($product->user)->patch(route('listed_products.update', $product->id), $this->validData() + [
            'image' => UploadedFile::fake()->image('new.png'),
        ])->assertSessionHasNoErrors()->assertRedirect(route('listed_products.show', $product->id));
        $fresh = $product->fresh();
        $this->assertSame('更新したタブレット', $fresh->product_name);
        $this->assertNotSame($product->img_path, $fresh->img_path);
        $disk->assertExists($fresh->img_path);
    }

    public function test_soft_delete_preserves_image_likes_and_purchase_history(): void
    {
        $product = $this->productWithImage();
        $sale = Sale::factory()->for($product)->create();
        $like = Like::factory()->for($product)->create();
        $this->actingAs($product->user)->get(route('listed_products.show', $product->id))
            ->assertSee('商品の削除確認')->assertSee('キャンセル')->assertSee('削除を確定する');
        $this->delete(route('listed_products.destroy', $product->id))->assertRedirect(route('mypage.show'))->assertSessionHas('status', '商品を削除しました。');
        $this->assertSoftDeleted($product);
        Storage::disk('public')->assertExists($product->img_path);
        $this->assertDatabaseHas('sales', ['id' => $sale->id]);
        $this->assertDatabaseHas('likes', ['id' => $like->id]);
        $this->get(route('mypage.show'))->assertDontSee(route('listed_products.show', $product->id), false);
        $this->actingAs($sale->user)->get(route('mypage.show'))->assertSee($product->product_name);
        $this->get(route('products.index'))->assertDontSee($product->product_name);
        $this->get(route('products.show', $product->id))->assertNotFound();
    }

    public function test_delete_failure_rolls_back_and_reports_error(): void
    {
        $product = $this->productWithImage();
        Product::deleted(function (): void {
            throw new RuntimeException('Simulated delete failure after SQL');
        });
        try {
            $this->actingAs($product->user)->delete(route('listed_products.destroy', $product->id))
                ->assertRedirect(route('listed_products.show', $product->id))->assertSessionHasErrors('deletion');
            $this->assertFalse($product->fresh()->trashed());
            Storage::disk('public')->assertExists($product->img_path);
        } finally {
            Product::flushEventListeners();
        }
    }

    public function test_missing_and_deleted_products_cannot_be_edited_updated_or_deleted(): void
    {
        $product = $this->productWithImage();
        $product->delete();
        $this->actingAs($product->user);
        foreach ([$product->id, 99999999] as $id) {
            $this->get(route('listed_products.edit', $id))->assertNotFound();
            $this->patch(route('listed_products.update', $id), $this->validData())->assertNotFound();
            $this->delete(route('listed_products.destroy', $id))->assertNotFound();
        }
    }
}
