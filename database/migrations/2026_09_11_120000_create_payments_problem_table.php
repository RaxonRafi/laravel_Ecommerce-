<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payment attempts that failed for a reason the customer cannot fix, and the
 * record of how staff resolved them.
 *
 * `order_id` is nullable on purpose: a callback bearing a transaction reference
 * that matches no order has no order to attach to, and that is precisely the row
 * most worth keeping.
 *
 * The table name is singular-ish by request; the model pins it explicitly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments_problem', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();

            $table->string('gateway')->default('sslcommerz');
            $table->string('gateway_reference')->nullable()->index();  // our tran_id
            $table->string('val_id')->nullable();                      // gateway-side validation id

            $table->string('reason')->index();                         // ProblemReason
            $table->text('message');

            // What the gateway said the payment was for. Deliberately separate from
            // the order total — a mismatch between the two is the whole point of a
            // validation_mismatch row.
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('currency', 3)->nullable();

            // Raw request/response, credentials and signatures stripped.
            $table->json('payload')->nullable();

            $table->string('resolution')->nullable();                  // ProblemResolution
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolution_reference')->nullable();
            $table->text('resolution_note')->nullable();

            $table->timestamps();

            // The admin list is "unresolved, newest first".
            $table->index(['resolved_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments_problem');
    }
};
