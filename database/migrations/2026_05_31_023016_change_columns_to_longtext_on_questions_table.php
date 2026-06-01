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
        Schema::table('questions', function (Blueprint $table) {
            $table->longText('text')->change();
            $table->longText('option_a')->change();
            $table->longText('option_b')->change();
            $table->longText('option_c')->change();
            $table->longText('option_d')->change();
            $table->longText('option_e')->change();
            $table->longText('explanation')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->text('text')->change();
            $table->string('option_a')->change();
            $table->string('option_b')->change();
            $table->string('option_c')->change();
            $table->string('option_d')->change();
            $table->string('option_e')->change();
            $table->text('explanation')->nullable()->change();
        });
    }
};
