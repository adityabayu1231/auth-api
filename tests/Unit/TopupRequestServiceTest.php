<?php

namespace Tests\Unit;

use App\Models\BankAccount;
use App\Models\TopupRequest;
use App\Models\User;
use App\Services\TopupRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TopupRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_generates_pending_topup_request_with_unique_code(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $bankAccount = BankAccount::factory()->create();
        $file = UploadedFile::fake()->image('proof.jpg')->size(1024);

        $service = new TopupRequestService();
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

        // Paksa semua kode 3 digit kecuali satu sudah dipakai pending,
        // supaya generateUniqueCode() harus mencari sampai ketemu kode itu.
        // Simulasi sederhana: buat satu topup dengan kode tertentu, pastikan
        // topup berikutnya tidak dapat kode yang sama.
        $existing = TopupRequest::factory()->create([
            'user_id' => $user->id,
            'bank_account_id' => $bankAccount->id,
            'unique_code' => '123',
            'status' => 'pending',
        ]);

        $file = UploadedFile::fake()->image('proof.jpg')->size(500);
        $service = new TopupRequestService();
        $topup = $service->create($user, $bankAccount->id, 50000, $file);

        $this->assertNotEquals('123', $topup->unique_code);
    }
}
