<?php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function pay(User $user, Order $order, int $amount): WalletTransaction
    {
        return DB::transaction(function () use ($user, $order, $amount) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            if ($wallet->balance < $amount) {
                throw new InsufficientBalanceException();
            }

            $balanceAfter = $wallet->balance - $amount;

            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'payment',
                'amount' => $amount,
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'balance_after' => $balanceAfter,
            ]);

            $wallet->update(['balance' => $balanceAfter]);

            return $transaction;
        });
    }

    public function refund(User $user, Order $order, int $amount): WalletTransaction
    {
        return DB::transaction(function () use ($user, $order, $amount) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            $balanceAfter = $wallet->balance + $amount;

            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'refund',
                'amount' => $amount,
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'balance_after' => $balanceAfter,
            ]);

            $wallet->update(['balance' => $balanceAfter]);

            return $transaction;
        });
    }
}
