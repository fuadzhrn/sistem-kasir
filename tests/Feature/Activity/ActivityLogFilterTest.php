<?php

namespace Tests\Feature\Activity;

class ActivityLogFilterTest extends ActivityLogTestCase
{
    public function test_filters_are_applied_after_access_scope_and_are_validated(): void
    {
        $owner = $this->user('owner');
        $branch = $this->branch();
        $this->log($owner, $branch, ['action' => 'sale_created', 'module' => 'sales', 'description' => 'Nota AUD-001']);
        $this->log($owner, $branch, ['action' => 'expense_created', 'module' => 'expenses', 'description' => 'Pengeluaran uji']);

        $this->actingAs($owner)
            ->get(route('activities.index', ['action' => 'sale_created', 'search' => 'AUD-001']))
            ->assertOk()
            ->assertSee('Nota AUD-001')
            ->assertDontSee('Pengeluaran uji');

        $this->actingAs($owner)
            ->get(route('activities.index', ['action' => 'aksi-tidak-valid']))
            ->assertSessionHasErrors('action');
    }
}
