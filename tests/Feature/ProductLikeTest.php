<?php

namespace Tests\Feature;

use App\Models\Like;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ProductLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_register_or_remove_likes(): void
    {
        $product = Product::factory()->create();
        $this->putJson(route('products.like.store', $product->id))->assertUnauthorized();
        $this->deleteJson(route('products.like.destroy', $product->id))->assertUnauthorized();
        $this->assertDatabaseCount('likes', 0);
    }

    public function test_registration_and_removal_are_idempotent_and_persist_on_detail(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('products.show', $product->id))->assertOk()->assertViewHas('liked', false)
            ->assertSee('aria-pressed="false"', false)->assertSee('お気に入り未登録');
        $this->putJson(route('products.like.store', $product->id))->assertOk()->assertExactJson(['liked' => true]);
        $like = Like::sole();
        $this->assertSame($user->id, $like->user_id);
        $this->assertSame($product->id, $like->product_id);
        $this->assertNotNull($like->created_at);
        $this->assertNotNull($like->updated_at);
        $this->putJson(route('products.like.store', $product->id))->assertOk()->assertJsonPath('liked', true);
        $this->assertDatabaseCount('likes', 1);
        $this->assertSame($like->id, Like::sole()->id);
        $this->get(route('products.show', $product->id))->assertViewHas('liked', true)->assertSee('aria-pressed="true"', false)->assertSee('favorite-button--active')->assertSee('お気に入り登録済み');
        $this->deleteJson(route('products.like.destroy', $product->id))->assertOk()->assertExactJson(['liked' => false]);
        $this->deleteJson(route('products.like.destroy', $product->id))->assertOk()->assertJsonPath('liked', false);
        $this->assertDatabaseCount('likes', 0);
        $this->get(route('products.show', $product->id))->assertViewHas('liked', false);
    }

    public function test_only_authenticated_users_like_is_changed_and_posted_ids_are_ignored(): void
    {
        $product = Product::factory()->create();
        $other_product = Product::factory()->create();
        $user = User::factory()->create();
        $other = User::factory()->create();
        $other_like = Like::factory()->for($other)->for($product)->create();
        $this->actingAs($user)->get(route('products.show', $product->id))->assertViewHas('liked', false);
        $this->putJson(route('products.like.store', $product->id), ['user_id' => $other->id, 'product_id' => $other_product->id, 'liked' => false])->assertOk()->assertJsonPath('liked', true);
        $this->assertDatabaseHas('likes', ['user_id' => $user->id, 'product_id' => $product->id]);
        $this->assertDatabaseMissing('likes', ['user_id' => $other->id, 'product_id' => $other_product->id]);
        $this->deleteJson(route('products.like.destroy', $product->id), ['user_id' => $other->id])->assertOk();
        $this->assertDatabaseHas('likes', ['id' => $other_like->id]);
        $this->actingAs($other)->get(route('products.show', $product->id))->assertViewHas('liked', true);
    }

    public function test_missing_and_deleted_products_cannot_be_liked_or_unliked(): void
    {
        $product = Product::factory()->deleted()->create();
        $like = Like::factory()->for($product)->create();
        $this->actingAs($like->user);
        foreach ([$product->id, 99999999] as $id) {
            $this->putJson(route('products.like.store', $id))->assertNotFound();
            $this->deleteJson(route('products.like.destroy', $id))->assertNotFound();
        }
        $this->assertDatabaseHas('likes', ['id' => $like->id]);
    }

    public function test_repeated_alternating_operations_do_not_create_duplicates(): void
    {
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create());
        for ($index = 0; $index < 4; $index++) {
            $this->putJson(route('products.like.store', $product->id))->assertOk();
            $this->putJson(route('products.like.store', $product->id))->assertOk();
            $this->assertDatabaseCount('likes', 1);
            $this->deleteJson(route('products.like.destroy', $product->id))->assertOk();
            $this->assertDatabaseCount('likes', 0);
        }
    }

    public function test_viewable_own_and_sold_out_products_can_be_liked(): void
    {
        $product = Product::factory()->soldOut()->create();
        $this->actingAs($product->user)->putJson(route('products.like.store', $product->id))->assertOk()->assertJsonPath('liked', true);
    }

    public function test_csrf_is_required_outside_test_bypass(): void
    {
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create());
        $this->app->instance('env', 'local');
        try {
            $this->putJson(route('products.like.store', $product->id))->assertStatus(419);
            $this->deleteJson(route('products.like.destroy', $product->id))->assertStatus(419);
        } finally {
            $this->app->instance('env', 'testing');
        }
        $this->assertDatabaseCount('likes', 0);
    }

    public function test_failure_after_insert_rolls_back_like(): void
    {
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create());
        Like::created(function (): void {
            throw new RuntimeException('Simulated insert failure');
        });
        try {
            $this->putJson(route('products.like.store', $product->id))->assertStatus(500);
            $this->assertDatabaseCount('likes', 0);
        } finally {
            Like::flushEventListeners();
        }
    }
}
