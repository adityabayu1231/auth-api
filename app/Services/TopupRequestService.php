<?php

namespace App\Services;

use App\Exceptions\TopupRequestAlreadyProcessedException;
use App\Models\TopupRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class TopupRequestService
{
    public function __construct(private WalletService $walletService) {}

    public function create(User $user, int $bankAccountId, int $amount, UploadedFile $proofImage): TopupRequest
    {
        $path = $proofImage->store('topup-proofs', 'public');

        return TopupRequest::create([
            'user_id' => $user->id,
            'bank_account_id' => $bankAccountId,
            'amount' => $amount,
            'unique_code' => $this->generateUniqueCode(),
            'proof_image_path' => $path,
            'status' => 'pending',
        ]);
    }

    public function approve(TopupRequest $topupRequest, User $admin): TopupRequest
    {
        if ($topupRequest->status !== 'pending') {
            throw new TopupRequestAlreadyProcessedException(
                "Topup request ini sudah berstatus '{$topupRequest->status}', tidak bisa diproses ulang."
            );
        }

        return DB::transaction(function () use ($topupRequest, $admin) {
            $topupRequest->update([
                'status' => 'approved',
                'verified_by' => $admin->id,
                'verified_at' => now(),
            ]);

            $this->walletService->topup($topupRequest->user, $topupRequest, $topupRequest->amount);

            return $topupRequest->fresh();
        });
    }

    public function reject(TopupRequest $topupRequest, User $admin): TopupRequest
    {
        if ($topupRequest->status !== 'pending') {
            throw new TopupRequestAlreadyProcessedException(
                "Topup request ini sudah berstatus '{$topupRequest->status}', tidak bisa diproses ulang."
            );
        }

        $topupRequest->update([
            'status' => 'rejected',
            'verified_by' => $admin->id,
            'verified_at' => now(),
        ]);

        return $topupRequest->fresh();
    }

    /**
     * Generate kode 3 digit acak yang belum dipakai oleh TopupRequest lain
     * yang masih berstatus pending — hindari duplikat kode untuk pencocokan
     * transfer manual (lihat plan.md ┬º10).
     */
    private function generateUniqueCode(): string
    {
        do {
            $code = str_pad((string) \random_int(0, 999), 3, '0', STR_PAD_LEFT);
            $exists = TopupRequest::where('unique_code', $code)
                ->where('status', 'pending')
                ->exists();
        } while ($exists);

        return $code;
    }

    public function listByStatus(?string $status, int $perPage = 15)
    {
        $query = TopupRequest::query()->orderByDesc('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }
}
