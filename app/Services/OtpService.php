<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use App\Notifications\SendOtpNotification;
use Illuminate\Support\Carbon;

class OtpService
{
    public function generate(User $user): OtpCode
    {
        $code = str_pad((string) \random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp = OtpCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'expires_at' => Carbon::now()->addMinutes(5),
        ]);

        $user->notify(new SendOtpNotification($code));

        return $otp;
    }

    public function verify(User $user, string $code): array
    {
        $otp = OtpCode::where('user_id', $user->id)
            ->latest('id')
            ->first();

        if (! $otp || $otp->code !== $code) {
            return ['valid' => false, 'reason' => 'invalid'];
        }

        if ($otp->used_at !== null) {
            return ['valid' => false, 'reason' => 'used'];
        }

        if ($otp->expires_at->isPast()) {
            return ['valid' => false, 'reason' => 'expired'];
        }

        $otp->update(['used_at' => Carbon::now()]);

        return ['valid' => true, 'reason' => null];
    }
}
