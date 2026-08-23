<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CafeIdForeignKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_inserting_user_with_nonexistent_cafe_id_fails(): void
    {
        // Pastikan FK enforcement aktif untuk SQLite (default-nya OFF per koneksi)
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::factory()->create([
            'cafe_id' => 9999, // id yang pasti tidak ada di tabel cafes
        ]);
    }

    public function test_user_cafe_id_is_nullable(): void
    {
        $user = User::factory()->create([
            'cafe_id' => null,
        ]);

        $this->assertNull($user->cafe_id);
    }
}
