<?php

namespace Tests\Feature\Report;

class ReportFilterValidationTest extends ReportTestCase
{
    public function test_pagination_sort_search_and_custom_dates_are_validated(): void
    {
        $owner = $this->createUser('owner');

        $this->getReport($owner, 'sales', ['per_page' => 25, 'sort' => 'date'])->assertOk();
        $this->getReport($owner, 'sales', ['per_page' => 10])
            ->assertRedirect()->assertSessionHasErrors('per_page');
        $this->getReport($owner, 'sales', ['sort' => 'password'])
            ->assertRedirect()->assertSessionHasErrors('sort');
        $this->getReport($owner, 'sales', ['search' => str_repeat('x', 101)])
            ->assertRedirect()->assertSessionHasErrors('search');
        $this->getReport($owner, 'sales', [
            'period' => 'custom',
            'date_from' => '2026-07-20',
            'date_to' => '2026-07-10',
        ])->assertRedirect()->assertSessionHasErrors('date_to');
        $this->getReport($owner, 'sales', [
            'period' => 'custom',
            'date_from' => '2020-01-01',
            'date_to' => '2026-07-25',
        ])->assertRedirect()->assertSessionHasErrors('date_to');
    }
}
