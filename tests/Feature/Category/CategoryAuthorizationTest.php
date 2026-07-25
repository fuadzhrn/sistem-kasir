<?php

namespace Tests\Feature\Category;

use App\Models\Category;
use Tests\Feature\MasterDataTestCase;

class CategoryAuthorizationTest extends MasterDataTestCase
{
    public function test_admin_can_manage_but_cannot_delete_category(): void
    {
        $admin = $this->createUser('admin');
        $category = Category::factory()->create();
        $this->actingAs($admin)->post(route('categories.store'), [
            'name' => 'Kategori Admin',
            'description' => null,
        ])->assertRedirect();
        $this->assertDatabaseHas('categories', ['slug' => 'kategori-admin']);
        $this->actingAs($admin)->get(route('categories.show', $category))->assertOk();
        $this->actingAs($admin)->get(route('categories.edit', $category))->assertOk();
        $this->actingAs($admin)->patch(route('categories.status.update', $category), ['is_active' => false])
            ->assertRedirect();
        $this->actingAs($admin)->delete(route('categories.destroy', $category))->assertForbidden();
    }

    public function test_cashier_direct_url_manipulation_is_denied(): void
    {
        $cashier = $this->createUser('cashier');
        $category = Category::factory()->create();
        $this->actingAs($cashier)->get(route('categories.show', $category))->assertForbidden();
        $this->actingAs($cashier)->put(route('categories.update', $category), [
            'name' => 'Manipulasi',
            'description' => null,
        ])->assertForbidden();
        $this->actingAs($cashier)->delete(route('categories.destroy', $category))->assertForbidden();
    }
}
