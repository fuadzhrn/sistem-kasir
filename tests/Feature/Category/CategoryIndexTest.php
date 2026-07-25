<?php

namespace Tests\Feature\Category;

use App\Models\Category;
use Tests\Feature\MasterDataTestCase;

class CategoryIndexTest extends MasterDataTestCase
{
    public function test_owner_and_admin_can_search_filter_and_paginate_categories(): void
    {
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin');
        Category::factory()->count(16)->create();
        Category::factory()->create(['name' => 'Pupuk Organik', 'slug' => 'pupuk-organik', 'is_active' => false]);

        $this->actingAs($owner)->get(route('categories.index'))
            ->assertOk()
            ->assertViewHas('categories', fn ($items) => $items->count() === 15 && $items->total() === 17);
        $this->actingAs($admin)->get(route('categories.index', ['search' => 'Pupuk', 'status' => 'inactive']))
            ->assertOk()
            ->assertSeeText('Pupuk Organik')
            ->assertSeeText('Tambah Kategori');
    }

    public function test_cashier_cannot_open_category_module(): void
    {
        $this->actingAs($this->createUser('cashier'))->get(route('categories.index'))->assertForbidden();
    }
}
