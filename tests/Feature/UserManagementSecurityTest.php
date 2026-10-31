<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $manager;

    protected User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMinimalData();

        $this->owner = User::where('role', 'owner')->firstOrFail();
        $this->manager = User::where('role', 'manager')->firstOrFail();
        $this->cashier = User::where('role', 'cashier')->firstOrFail();
    }

    public function test_invalid_role_and_short_password_are_rejected(): void
    {
        $response = $this->actingAs($this->owner)->post(route('settings.users.store'), [
            'name' => 'Staf Baru',
            'email' => 'stafbaru@sigma.local',
            'password' => '123456',
            'role' => 'superadmin_illegal',
        ]);

        $response->assertSessionHasErrors(['role', 'password']);
        $this->assertDatabaseMissing('users', ['email' => 'stafbaru@sigma.local']);

        $updateResponse = $this->actingAs($this->owner)->put(route('settings.users.update', $this->cashier->id), [
            'name' => $this->cashier->name,
            'email' => $this->cashier->email,
            'role' => 'hacker',
            'password' => '123456',
        ]);

        $updateResponse->assertSessionHasErrors(['role', 'password']);

        $resetResponse = $this->actingAs($this->owner)->post(route('settings.users.reset-password', $this->cashier->id), [
            'new_password' => '123456',
        ]);

        $resetResponse->assertSessionHasErrors(['new_password']);
    }

    public function test_manager_cannot_create_owner_or_elevate_user_to_owner(): void
    {
        $createResponse = $this->actingAs($this->manager)->post(route('settings.users.store'), [
            'name' => 'Fake Owner',
            'email' => 'fakeowner@sigma.local',
            'password' => 'PasswordKuat123',
            'role' => 'owner',
        ]);

        $createResponse->assertStatus(403);
        $this->assertDatabaseMissing('users', ['email' => 'fakeowner@sigma.local']);

        $elevateResponse = $this->actingAs($this->manager)->put(route('settings.users.update', $this->cashier->id), [
            'name' => $this->cashier->name,
            'email' => $this->cashier->email,
            'role' => 'owner',
        ]);

        $elevateResponse->assertStatus(403);
        $this->assertSame('cashier', $this->cashier->fresh()->role);
    }

    public function test_manager_cannot_modify_reset_password_set_pin_or_toggle_owner_account(): void
    {
        $originalPasswordHash = $this->owner->password;
        $originalPinHash = $this->owner->manager_pin;

        $updateOwner = $this->actingAs($this->manager)->put(route('settings.users.update', $this->owner->id), [
            'name' => 'Hacked Owner',
            'email' => $this->owner->email,
            'role' => 'cashier',
        ]);
        $updateOwner->assertStatus(403);
        $this->assertSame('owner', $this->owner->fresh()->role);
        $this->assertNotSame('Hacked Owner', $this->owner->fresh()->name);

        $resetOwnerPassword = $this->actingAs($this->manager)->post(route('settings.users.reset-password', $this->owner->id), [
            'new_password' => 'PasswordBaru123',
        ]);
        $resetOwnerPassword->assertStatus(403);
        $this->assertSame($originalPasswordHash, $this->owner->fresh()->password);

        $setOwnerPin = $this->actingAs($this->manager)->post(route('settings.users.set-pin', $this->owner->id), [
            'manager_pin' => '999999',
        ]);
        $setOwnerPin->assertStatus(403);
        $this->assertSame($originalPinHash, $this->owner->fresh()->manager_pin);

        $toggleOwner = $this->actingAs($this->manager)->patch(route('settings.users.toggle-status', $this->owner->id));
        $toggleOwner->assertStatus(403);
        $this->assertTrue($this->owner->fresh()->is_active);
    }

    public function test_role_changes_are_recorded_in_audit_log(): void
    {
        AuditLog::query()->delete();

        $createResponse = $this->actingAs($this->owner)->post(route('settings.users.store'), [
            'name' => 'Budi Kasir',
            'email' => 'budikasir@sigma.local',
            'password' => 'PasswordKuat123',
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $createResponse->assertRedirect();
        $newUser = User::where('email', 'budikasir@sigma.local')->firstOrFail();
        $this->assertSame('cashier', $newUser->role);

        $updateResponse = $this->actingAs($this->owner)->put(route('settings.users.update', $newUser->id), [
            'name' => 'Budi Supervisor',
            'email' => 'budikasir@sigma.local',
            'role' => 'store_head',
            'is_active' => true,
        ]);

        $updateResponse->assertRedirect();
        $this->assertSame('store_head', $newUser->fresh()->role);

        $roleChangeLog = AuditLog::where('user_id', $this->owner->id)
            ->get()
            ->first(function (AuditLog $log) use ($newUser) {
                return ($log->old_values['role'] ?? null) === 'cashier'
                    && ($log->new_values['role'] ?? null) === 'store_head'
                    && ($log->new_values['target_user_id'] ?? $log->old_values['target_user_id'] ?? null) === $newUser->id;
            });

        $this->assertNotNull($roleChangeLog, 'Perubahan role harus tercatat di AuditLog dengan old_values dan new_values.');

        $resetResponse = $this->actingAs($this->owner)->post(route('settings.users.reset-password', $newUser->id), [
            'new_password' => 'PasswordBaru888',
        ]);
        $resetResponse->assertRedirect();
        $this->assertTrue(Hash::check('PasswordBaru888', $newUser->fresh()->password));
    }
}
