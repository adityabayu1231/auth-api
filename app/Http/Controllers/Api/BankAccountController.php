<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;

class BankAccountController extends Controller
{
    public function index()
    {
        $bankAccounts = BankAccount::all();

        return response()->json([
            'success' => true,
            'data' => $bankAccounts,
            'message' => 'Daftar rekening bank berhasil diambil.',
        ]);
    }
}
