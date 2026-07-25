<?php

namespace Tests\Feature\User;

use Illuminate\Support\Facades\Hash;

class UserPasswordResetTest extends UserTestCase
{
    public function test_owner_can_reset_another_user_password_and_old_password_stops_working(): void
    {
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier', null, [
            'username' => 'kasir.reset',
            'password' => 'PasswordLama123',
        ]);

        $this->actingAs($owner)
            ->get(route('users.password.edit', $cashier))
            ->assertOk()
            ->assertSeeText($cashier->name)
            ->assertDontSee('PasswordLama123');

        $this->actingAs($owner)
            ->put(route('users.password.update', $cashier), [
                'password' => 'PasswordBaru123',
                'password_confirmation' => 'PasswordBaru123',
            ])
            ->assertRedirect(route('users.show', $cashier));

        $this->assertTrue(Hash::check('PasswordBaru123', $cashier->fresh()->password));
        $this->assertFalse(Hash::check('PasswordLama123', $cashier->fresh()->password));
        $this->assertNotSame('PasswordBaru123', $cashier->fresh()->password);
    }

    public function test_admin_cashier_and_owner_self_reset_are_denied(): void
    {
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin');
        $cashier = $this->createUser('cashier', $admin->branch);

        $this->actingAs($admin)->get(route('users.password.edit', $cashier))->assertForbidden();
        $this->actingAs($cashier)->put(route('users.password.update', $cashier), [
            'password' => 'PasswordBaru123',
            'password_confirmation' => 'PasswordBaru123',
        ])->assertForbidden();
        $this->actingAs($owner)->get(route('users.password.edit', $owner))->assertForbidden();
    }

    public function test_reset_password_validates_strength_and_confirmation(): void
    {
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin');

        $this->actingAs($owner)
            ->put(route('users.password.update', $admin), [
                'password' => 'lemah',
                'password_confirmation' => 'berbeda',
            ])
            ->assertSessionHasErrors('password');
    }
}
