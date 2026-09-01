<?php

namespace App\Services;

use App\Models\TopupRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class TopupRequestService
{
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
}
