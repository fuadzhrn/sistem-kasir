<?php

namespace Tests\Feature\Expense;

use App\Models\Branch;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Testing\TestResponse;

class ExpensePaginationTest extends ExpenseTestCase
{
    public function test_first_and_last_pages_use_indonesian_pagination_with_preserved_filters(): void
    {
        $branch = $this->createBranch('PGN');
        $owner = $this->createUser('owner');
        $category = $this->createCategory(['name' => 'Pagination']);
        $this->createExpenses(30, $branch, $owner, $category);
        $filters = [
            'search' => 'Pagination',
            'branch_id' => $branch->id,
            'expense_category_id' => $category->id,
            'status' => 'pending',
            'created_by' => $owner->id,
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
            'per_page' => 15,
        ];

        $firstPage = $this->actingAs($owner)->get(route('expenses.index', $filters));

        $firstPage->assertOk()
            ->assertSeeText('Menampilkan 1–15 dari 30 pengeluaran')
            ->assertSeeText('Sebelumnya')
            ->assertSeeText('Berikutnya')
            ->assertSee('app-pagination__link--active', false)
            ->assertSee('aria-disabled="true"', false)
            ->assertDontSeeText('pagination.previous')
            ->assertDontSeeText('pagination.next')
            ->assertDontSeeText('Showing');
        $this->assertPaginationUrlPreservesFilters($firstPage, 2, $filters);

        $lastPage = $this->actingAs($owner)->get(route('expenses.index', [
            ...$filters,
            'page' => 2,
        ]));

        $lastPage->assertOk()
            ->assertSeeText('Menampilkan 16–30 dari 30 pengeluaran')
            ->assertSee('rel="prev"', false)
            ->assertSee('aria-label="Berikutnya"', false)
            ->assertSee('app-pagination__link--disabled', false);
    }

    public function test_middle_page_has_previous_next_and_clear_active_state(): void
    {
        $branch = $this->createBranch('MID');
        $owner = $this->createUser('owner');
        $category = $this->createCategory(['name' => 'Pagination Tengah']);
        $this->createExpenses(45, $branch, $owner, $category);

        $response = $this->actingAs($owner)->get(route('expenses.index', [
            'per_page' => 15,
            'page' => 2,
        ]));

        $response->assertOk()
            ->assertSeeText('Menampilkan 16–30 dari 45 pengeluaran')
            ->assertSee('rel="prev"', false)
            ->assertSee('rel="next"', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('aria-label="Halaman 2"', false);
    }

    public function test_single_page_keeps_summary_and_hides_page_controls(): void
    {
        $branch = $this->createBranch('ONE');
        $owner = $this->createUser('owner');
        $category = $this->createCategory(['name' => 'Pagination Tunggal']);
        $this->createExpenses(1, $branch, $owner, $category);

        $this->actingAs($owner)->get(route('expenses.index'))
            ->assertOk()
            ->assertSeeText('Menampilkan 1–1 dari 1 pengeluaran')
            ->assertDontSee('app-pagination__links', false);
    }

    /**
     * @param  array<string, int|string>  $filters
     */
    private function assertPaginationUrlPreservesFilters(
        TestResponse $response,
        int $page,
        array $filters,
    ): void {
        preg_match_all('/href="([^"]+)"/', $response->getContent(), $matches);
        $pageUrl = collect($matches[1])
            ->map(static fn (string $url): string => html_entity_decode($url))
            ->first(static fn (string $url): bool => str_contains($url, 'page='.$page));

        $this->assertNotNull($pageUrl, "Tautan halaman {$page} tidak ditemukan.");
        parse_str((string) parse_url($pageUrl, PHP_URL_QUERY), $query);

        foreach ($filters as $key => $value) {
            $this->assertSame((string) $value, (string) ($query[$key] ?? null));
        }

        $this->assertSame((string) $page, (string) ($query['page'] ?? null));
    }

    private function createExpenses(
        int $count,
        Branch $branch,
        User $owner,
        ExpenseCategory $category,
    ): void {
        foreach (range(1, $count) as $number) {
            $this->createExpense($branch, $owner, $category, [
                'description' => "Pagination pengeluaran {$number}",
            ]);
        }
    }
}
