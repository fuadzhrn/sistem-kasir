<?php

namespace Tests\Feature\Expense;

use App\Models\ExpenseCategory;

class ExpenseCategoryManagementTest extends ExpenseTestCase
{
    public function test_owner_and_admin_can_create_update_and_change_status(): void
    {
        $branch = $this->createBranch();
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branch);

        $this->actingAs($owner)->post(route('expense-categories.store'), [
            'name' => 'Biaya Internet',
            'description' => 'Internet toko',
        ])->assertRedirect();
        $category = ExpenseCategory::query()->where('slug', 'biaya-internet')->firstOrFail();
        $this->assertSame($owner->id, $category->created_by);

        $this->actingAs($admin)->put(route('expense-categories.update', $category), [
            'name' => 'Internet dan Telepon',
            'description' => 'Komunikasi toko',
        ])->assertRedirect(route('expense-categories.index'));
        $this->actingAs($admin)->patch(route('expense-categories.status.update', $category), [
            'is_active' => '0',
        ])->assertRedirect();

        $category->refresh();
        $this->assertSame('internet-dan-telepon', $category->slug);
        $this->assertFalse($category->is_active);
        $this->assertSame($admin->id, $category->updated_by);
    }

    public function test_duplicate_names_receive_unique_slugs(): void
    {
        $owner = $this->createUser('owner');

        foreach (['Biaya Umum', 'Biaya-Umum'] as $index => $name) {
            $this->actingAs($owner)->post(route('expense-categories.store'), [
                'name' => $name,
                'description' => 'Kategori '.($index + 1),
            ])->assertRedirect();
        }

        $this->assertDatabaseHas('expense_categories', ['slug' => 'biaya-umum']);
        $this->assertDatabaseHas('expense_categories', ['slug' => 'biaya-umum-2']);
    }
}
