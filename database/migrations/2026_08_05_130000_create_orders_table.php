<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();

            // Orders outlive users for accounting purposes, so deletion is restricted
            // rather than cascaded.
            $table->foreignId('user_id')->constrained()->restrictOnDelete();

            $table->string('order_number')->unique();
            $table->string('status')->default('pending')->index();

            // Money is decimal, never float. Totals are stored rather than recomputed
            // so a later price or shipping change cannot rewrite history.
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('shipping_total', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2);
            $table->string('currency', 3)->default('BDT');

            $table->string('payment_method')->default('cod');
            $table->string('payment_status')->default('pending')->index();

            // Coupon is snapshotted: the code must survive the coupon being edited
            // or deleted later.
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('coupon_code')->nullable();

            $table->string('shipping_name');
            $table->string('shipping_phone');
            $table->text('shipping_address');
            $table->foreignId('shipping_country_id')->constrained('countries')->restrictOnDelete();
            $table->string('shipping_city');
            $table->text('notes')->nullable();

            $table->timestamp('placed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
