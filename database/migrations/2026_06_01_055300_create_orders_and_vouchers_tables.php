<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->integer('discount_value'); // percentage (0-100) or fixed amount in rupiah
            $table->integer('min_purchase')->default(0);
            $table->integer('max_uses')->nullable(); // null = unlimited
            $table->integer('used_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('bundle_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('amount'); // original price
            $table->string('voucher_code')->nullable();
            $table->integer('discount')->default(0);
            $table->integer('final_amount'); // amount after discount
            $table->string('payment_proof')->nullable(); // uploaded image path
            $table->enum('status', ['pending', 'confirmed', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
        Schema::dropIfExists('vouchers');
    }
};
