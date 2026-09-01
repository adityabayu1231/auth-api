<?php

use App\Models\BankAccount;
use App\Models\TopupRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'cafe_manager', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    Storage::fake('public');
});

it('creates a topup request with pending status and generates unique code', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole('customer');
    $bankAccount = BankAccount::factory()->create();
    $file = UploadedFile::fake()->image('proof.jpg')->size(1024); // 1MB

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($user)->postJson('/api/wallet/topup-request', [
        'bank_account_id' => $bankAccount->id,
        'amount' => 100000,
        'proof_image' => $file,
    ]);

    $response->assertStatus(201)
        ->assertJson(['success' => true])
        ->assertJsonPath('data.status', 'pending');

    $this->assertDatabaseHas('topup_requests', [
        'user_id' => $user->id,
        'bank_account_id' => $bankAccount->id,
        'amount' => 100000,
        'status' => 'pending',
    ]);

    $topup = TopupRequest::where('user_id', $user->id)->first();
    $this->assertEquals(3, strlen($topup->unique_code));
    Storage::disk('public')->assertExists($topup->proof_image_path);
});

it('rejects proof image larger than 2MB with 422', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole('customer');
    $bankAccount = BankAccount::factory()->create();
    $file = UploadedFile::fake()->image('proof.jpg')->size(3072); // 3MB

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($user)->postJson('/api/wallet/topup-request', [
        'bank_account_id' => $bankAccount->id,
        'amount' => 100000,
        'proof_image' => $file,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['proof_image']);

    $this->assertEquals(0, TopupRequest::count());
});

it('rejects proof image that is not jpeg or png', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole('customer');
    $bankAccount = BankAccount::factory()->create();
    $file = UploadedFile::fake()->create('proof.pdf', 500, 'application/pdf');

    /** @var \Tests\TestCase $this */
    $response = $this->actingAs($user)->postJson('/api/wallet/topup-request', [
        'bank_account_id' => $bankAccount->id,
        'amount' => 100000,
        'proof_image' => $file,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['proof_image']);

    $this->assertEquals(0, TopupRequest::count());
});

it('rejects request without authentication', function () {
    $bankAccount = BankAccount::factory()->create();
    $file = UploadedFile::fake()->image('proof.jpg')->size(1024);

    /** @var \Tests\TestCase $this */
    $response = $this->postJson('/api/wallet/topup-request', [
        'bank_account_id' => $bankAccount->id,
        'amount' => 100000,
        'proof_image' => $file,
    ]);

    $response->assertStatus(401);
});
