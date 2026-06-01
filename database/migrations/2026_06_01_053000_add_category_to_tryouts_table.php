<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tryouts', function (Blueprint $table) {
            $table->string('category')->default('SKD')->after('duration_minutes');
            // SKD = full (TWK + TIU + TKP), TWK = hanya TWK, TIU = hanya TIU, TKP = hanya TKP
        });
    }

    public function down(): void
    {
        Schema::table('tryouts', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
