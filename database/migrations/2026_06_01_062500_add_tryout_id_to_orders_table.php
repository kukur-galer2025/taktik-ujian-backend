<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('tryout_id')->nullable()->after('bundle_id');
            // Nullable because an order is EITHER for a bundle OR a tryout
            
            // Adjust bundle_id to be nullable too, since old orders might have it but future orders might only have tryout_id
            $table->unsignedBigInteger('bundle_id')->nullable()->change();
            
            // Note: If bundle_id was created as constrained previously without cascade on delete, 
            // you might need to handle foreign keys carefully. We'll assume simple integer references for now.
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('tryout_id');
            // Reverting bundle_id to not null might fail if there are nulls, so we leave it nullable in down for safety or assume it was nullable.
        });
    }
};
