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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color')->nullable();
            $table->timestamps();
        });

        // Seed initial categories
        $categoriesData = [
            ['name' => 'SKD Umum', 'slug' => 'SKD', 'color' => 'bg-brand-50,text-brand-700,border-brand-200,from-brand-50,to-brand-100'],
            ['name' => 'Khusus TWK', 'slug' => 'TWK', 'color' => 'bg-rose-50,text-rose-700,border-rose-200,from-rose-50,to-rose-100'],
            ['name' => 'Khusus TIU', 'slug' => 'TIU', 'color' => 'bg-blue-50,text-blue-700,border-blue-200,from-blue-50,to-blue-100'],
            ['name' => 'Khusus TKP', 'slug' => 'TKP', 'color' => 'bg-emerald-50,text-emerald-700,border-emerald-200,from-emerald-50,to-emerald-100'],
            ['name' => 'CPNS', 'slug' => 'CPNS', 'color' => 'bg-orange-50,text-orange-700,border-orange-200,from-orange-50,to-orange-100'],
            ['name' => 'Kedinasan', 'slug' => 'KEDINASAN', 'color' => 'bg-indigo-50,text-indigo-700,border-indigo-200,from-indigo-50,to-indigo-100'],
        ];

        foreach ($categoriesData as $data) {
            \Illuminate\Support\Facades\DB::table('categories')->insert(array_merge($data, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Add category_id to tryouts and map existing data
        Schema::table('tryouts', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('duration_minutes')->constrained('categories')->nullOnDelete();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tryouts', function (Blueprint $table) {
            $table->string('category')->default('SKD')->after('duration_minutes');
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });

        Schema::dropIfExists('categories');
    }
};
