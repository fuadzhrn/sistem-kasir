<?php

namespace Tests\Feature\Category;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Tests\Feature\MasterDataTestCase;

class CategoryManagementTest extends MasterDataTestCase
{
    public function test_owner_can_create_edit_and_change_category_status_with_normalization(): void
    {
        $owner = $this->createUser('owner');
        $this->actingAs($owner)->post(route('categories.store'), [
            'name' => '  Pupuk   Organik  ',
            'description' => '  Kategori pupuk  ',
            'slug' => 'tidak-dipercaya',
            'is_active' => false,
        ])->assertRedirect();

        $category = Category::query()->where('slug', 'pupuk-organik')->firstOrFail();
        $this->assertSame('Pupuk Organik', $category->name);
        $this->assertTrue($category->is_active);

        $this->actingAs($owner)->put(route('categories.update', $category), [
            'name' => 'Pupuk Hayati',
            'description' => null,
            'is_active' => false,
        ])->assertRedirect(route('categories.show', $category));
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'slug' => 'pupuk-hayati', 'is_active' => true]);

        $this->actingAs($owner)->patch(route('categories.status.update', $category), ['is_active' => false])
            ->assertRedirect();
        $this->assertFalse($category->fresh()->is_active);
    }

    public function test_category_name_is_required_case_insensitive_unique_and_slug_is_unique(): void
    {
        $owner = $this->createUser('owner');
        Category::factory()->create(['name' => 'Pestisida', 'slug' => 'pestisida']);

        $this->actingAs($owner)->post(route('categories.store'), ['name' => ''])->assertSessionHasErrors('name');
        $this->actingAs($owner)->post(route('categories.store'), ['name' => 'PESTISIDA'])
            ->assertSessionHasErrors('name');

        Category::factory()->create(['name' => 'A Plus B', 'slug' => 'a-b']);
        $this->actingAs($owner)->post(route('categories.store'), ['name' => 'A & B'])->assertRedirect();
        $this->assertDatabaseHas('categories', ['name' => 'A & B', 'slug' => 'a-b-2']);
    }

    public function test_owner_only_deletes_unused_category_and_used_category_is_protected(): void
    {
        $owner = $this->createUser('owner');
        $unused = Category::factory()->create();
        $used = Category::factory()->create();
        Product::factory()->create(['category_id' => $used, 'unit_id' => Unit::factory()]);

        $this->actingAs($owner)->delete(route('categories.destroy', $unused))->assertRedirect(route('categories.index'));
        $this->assertModelMissing($unused);
        $this->actingAs($owner)->delete(route('categories.destroy', $used))->assertSessionHasErrors('delete');
        $this->assertModelExists($used);
    }
}
