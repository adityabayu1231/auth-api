<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->foreignId('cafe_id')->nullable()->after('phone')->nullOnDelete();
            // $table->foreignId('cafe_id')->nullable()->after('phone')
            //     ->constrained('cafes')->nullOnDelete();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cafe_id');
            $table->dropColumn(['phone', 'deleted_at']);
        });
    }
};