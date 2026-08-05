<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per payment attempt, never updated in place. A failed attempt followed
 * by a successful one leaves two rows, which is what makes this an audit trail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            $table->string('gateway');                       // cod | sslcommerz | bkash
            $table->string('gateway_reference')->nullable()->index();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('BDT');
            $table->string('status')->default('pending')->index();

            // Raw gateway request/response, kept for reconciliation and disputes.
            $table->json('payload')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
