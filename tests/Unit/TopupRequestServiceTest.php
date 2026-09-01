<?php

namespace Tests\Unit;

use App\Exceptions\TopupRequestAlreadyProcessedException;
use App\Models\BankAccount;
use App\Models\TopupRequest;
use App\Models\User;
use App\Models\Wallet;
use App\Services\TopupRequestService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TopupRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeService(): TopupRequestService
    {
        return new TopupRequestService(new WalletService());
    }

    public function test_create_generates_pending_topup_request_with_unique_code(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $bankAccount = BankAccount::factory()->create();
        $file = UploadedFile::fake()->image('proof.jpg')->size(1024);

        $service = $this->makeService();
        $topup = $service->create($user, $bankAccount->id, 100000, $file);

        $this->assertEquals('pending', $topup->status);
        $this->assertEquals(3, strlen($topup->unique_code));
        Storage::disk('public')->assertExists($topup->proof_image_path);
    }

    public function test_generates_different_unique_code_when_previous_pending_code_exists(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $bankAccount = BankAccount::factory()->create();

        TopupRequest::factory()->create([
            'user_id' => $user->id,
            'bank_account_id' => $bankAccount->id,
            'unique_code' => '123',
            'status' => 'pending',
        ]);

        $file = UploadedFile::fake()->image('proof.jpg')->size(500);
        $service = $this->makeService();
        $topup = $service->create($user, $bankAccount->id, 50000, $file);

        $this->assertNotEquals('123', $topup->unique_code);
    }

    public function test_approve_adds_balance_and_records_wallet_transaction(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();
        Wallet::where('user_id', $user->id)->update(['balance' => 10000]);
        $topupRequest = TopupRequest::factory()->create([
            'user_id' => $user->id,
            'amount' => 100000,
            'status' => 'pending',
        ]);

        $service = $this->makeService();
        $approved = $service->approve($topupRequest, $admin);

        $this->assertEquals('approved', $approved->status);
        $this->assertEquals($admin->id, $approved->verified_by);
        $this->assertNotNull($approved->verified_at);
        $this->assertEquals(110000, $user->wallet->fresh()->balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'type' => 'topup',
            'amount' => 100000,
            'reference_type' => 'topup_request',
            'reference_id' => $topupRequest->id,
            'balance_after' => 110000,
        ]);
    }

    public function test_reject_does_not_change_balance_or_create_transaction(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();
        Wallet::where('user_id', $user->id)->update(['balance' => 10000]);
        $topupRequest = TopupRequest::factory()->create([
            'user_id' => $user->id,
            'amount' => 100000,
            'status' => 'pending',
        ]);

        $service = $this->makeService();
        $rejected = $service->reject($topupRequest, $admin);

        $this->assertEquals('rejected', $rejected->status);
        $this->assertEquals($admin->id, $rejected->verified_by);
        $this->assertEquals(10000, $user->wallet->fresh()->balance);
        $this->assertDatabaseMissing('wallet_transactions', [
            'reference_type' => 'topup_request',
            'reference_id' => $topupRequest->id,
        ]);
    }

    public function test_approve_rejects_double_processing(): void
    {
        $admin = User::factory()->create();
        $topupRequest = TopupRequest::factory()->create(['status' => 'approved']);

        $service = $this->makeService();

        $this->expectException(TopupRequestAlreadyProcessedException::class);
        $service->approve($topupRequest, $admin);
    }

    public function test_reject_rejects_double_processing(): void
    {
        $admin = User::factory()->create();
        $topupRequest = TopupRequest::factory()->create(['status' => 'rejected']);

        $service = $this->makeService();

        $this->expectException(TopupRequestAlreadyProcessedException::class);
        $service->reject($topupRequest, $admin);
    }
}
