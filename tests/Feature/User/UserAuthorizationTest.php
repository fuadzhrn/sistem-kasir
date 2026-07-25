<?php

namespace Tests\Feature\User;

use App\Models\Branch;
use Illuminate\Support\Facades\Route;

class UserAuthorizationTest extends UserTestCase
{
    public function test_admin_is_read_only_and_url_manipulation_to_other_branch_is_hidden(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $admin = $this->createUser('admin', $branchA);
        $ownCashier = $this->createUser('cashier', $branchA);
        $otherCashier = $this->createUser('cashier', $branchB);

        $this->actingAs($admin)->get(route('users.show', $ownCashier))->assertOk();
        $this->actingAs($admin)->get(route('users.show', $otherCashier))->assertNotFound();
        $this->actingAs($admin)->get(route('users.create'))->assertForbidden();
        $this->actingAs($admin)->get(route('users.edit', $ownCashier))->assertForbidden();
        $this->actingAs($admin)->patch(route('users.status.update', $ownCashier), ['is_active' => false])->assertForbidden();
        $this->actingAs($admin)->get(route('users.password.edit', $ownCashier))->assertForbidden();
    }

    public function test_cashier_and_guest_cannot_open_user_management_urls(): void
    {
        $cashier = $this->createUser('cashier');

        $this->get(route('users.index'))->assertRedirect(route('login'));
        $this->actingAs($cashier)->get(route('users.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('users.show', $cashier))->assertForbidden();
        $this->actingAs($cashier)->put(route('users.update', $cashier), [])->assertForbidden();
    }

    public function test_create_form_has_csrf_and_no_delete_route_exists(): void
    {
        $owner = $this->createUser('owner');

        $this->actingAs($owner)
            ->get(route('users.create'))
            ->assertOk()
            ->assertSee('name="_token"', false);

        $this->assertFalse(Route::has('users.destroy'));
    }
}
