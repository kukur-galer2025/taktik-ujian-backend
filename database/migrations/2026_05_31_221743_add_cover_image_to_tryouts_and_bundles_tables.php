<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tryouts', function (Blueprint $table) {
            $table->string('cover_image')->nullable()->after('duration_minutes');
        });

        Schema::table('bundles', function (Blueprint $table) {
            $table->string('cover_image')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tryouts', function (Blueprint $table) {
            $table->dropColumn('cover_image');
        });
        
        Schema::table('bundles', function (Blueprint $table) {
            $table->dropColumn('cover_image');
        });
    }
};
