<?php

use App\Models\TopupRequest;
use App\Models\User;
use App\Models\Wallet;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'cafe_manager', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
});

it('lets admin approve a pending topup request and credits wallet', function () {
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    /** @var User $user */
    $user = User::factory()->create();
    Wallet::where('user_id', $user->id)->update(['balance' => 5000]);
    $topupRequest = TopupRequest::factory()->create([
        'user_id' => $user->id,
        'amount' => 100000,
        'status' => 'pending',
    ]);

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($admin)->patchJson("/api/admin/topup-requests/{$topupRequest->id}/approve");

    $response->assertOk()->assertJsonPath('data.status', 'approved');
    $this->assertEquals(105000, $user->wallet->fresh()->balance);
});

it('lets admin reject a pending topup request without changing balance', function () {
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    /** @var User $user */
    $user = User::factory()->create();
    Wallet::where('user_id', $user->id)->update(['balance' => 5000]);
    $topupRequest = TopupRequest::factory()->create([
        'user_id' => $user->id,
        'amount' => 100000,
        'status' => 'pending',
    ]);

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($admin)->patchJson("/api/admin/topup-requests/{$topupRequest->id}/reject");

    $response->assertOk()->assertJsonPath('data.status', 'rejected');
    $this->assertEquals(5000, $user->wallet->fresh()->balance);
});

it('rejects non-admin from approving topup request', function () {
    /** @var User $customer */
    $customer = User::factory()->create();
    $customer->assignRole('customer');
    $topupRequest = TopupRequest::factory()->create(['status' => 'pending']);

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($customer)->patchJson("/api/admin/topup-requests/{$topupRequest->id}/approve");

    $response->assertForbidden();
});

it('rejects approving an already processed topup request', function () {
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $topupRequest = TopupRequest::factory()->create(['status' => 'approved']);

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($admin)->patchJson("/api/admin/topup-requests/{$topupRequest->id}/approve");

    $response->assertStatus(422)->assertJson(['success' => false]);
});
