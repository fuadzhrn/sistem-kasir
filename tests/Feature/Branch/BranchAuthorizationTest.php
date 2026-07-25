<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use Illuminate\Support\Facades\Route;

class BranchAuthorizationTest extends BranchTestCase
{
    public function test_guest_admin_and_cashier_cannot_mutate_branches(): void
    {
        $branch = Branch::factory()->create();
        $admin = $this->createUser('admin', $branch);
        $cashier = $this->createUser('cashier', $branch);
        $payload = ['code' => 'AMAN', 'name' => 'Aman'];

        $this->get(route('branches.create'))->assertRedirect(route('login'));
        $this->actingAs($admin)->get(route('branches.edit', $branch))->assertForbidden();
        $this->actingAs($admin)->put(route('branches.update', $branch), $payload)->assertForbidden();
        $this->actingAs($cashier)->get(route('branches.show', $branch))->assertForbidden();
        $this->actingAs($cashier)->patch(route('branches.status.update', $branch), ['is_active' => false])->assertForbidden();
    }

    public function test_forms_have_csrf_and_no_delete_route_exists(): void
    {
        $owner = $this->createUser('owner');

        $this->actingAs($owner)
            ->get(route('branches.create'))
            ->assertOk()
            ->assertSee('name="_token"', false);

        $this->assertFalse(Route::has('branches.destroy'));
    }
}
