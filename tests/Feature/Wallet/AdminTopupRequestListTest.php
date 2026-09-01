<?php

use App\Models\BankAccount;
use App\Models\TopupRequest;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'cafe_manager', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
});

it('lets admin list pending topup requests filtered by status', function () {
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    TopupRequest::factory()->count(2)->create(['status' => 'pending']);
    TopupRequest::factory()->count(3)->create(['status' => 'approved']);

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($admin)->getJson('/api/admin/topup-requests?status=pending');

    $response->assertOk()
        ->assertJsonPath('data.pagination.total', 2)
        ->assertJsonCount(2, 'data.items');
});

it('returns all topup requests when no status filter given', function () {
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    TopupRequest::factory()->count(2)->create(['status' => 'pending']);
    TopupRequest::factory()->count(1)->create(['status' => 'rejected']);

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($admin)->getJson('/api/admin/topup-requests');

    $response->assertOk()->assertJsonPath('data.pagination.total', 3);
});

it('rejects non-admin from listing topup requests', function () {
    /** @var User $customer */
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($customer)->getJson('/api/admin/topup-requests');

    $response->assertForbidden();
});

it('lets any authenticated user list bank accounts', function () {
    /** @var User $customer */
    $customer = User::factory()->create();
    $customer->assignRole('customer');
    BankAccount::factory()->count(2)->create();

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($customer)->getJson('/api/bank-accounts');

    $response->assertOk();
    $this->assertCount(2, $response->json('data'));
});

it('rejects unauthenticated access to bank accounts', function () {
    /** @var \Tests\TestCase $this */
    $response = $this->getJson('/api/bank-accounts');

    $response->assertStatus(401);
});
