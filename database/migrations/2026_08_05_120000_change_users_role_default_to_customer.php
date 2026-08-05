<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The `role` column was created with a default of 'admin', and the Laravel UI
 * registration flow never sets it — so every self-registered user became an
 * administrator. The default is flipped to 'customer' here.
 *
 * Existing rows are deliberately left untouched: there is no way to distinguish
 * a legitimate admin from one created by this bug, and demoting a real admin
 * could lock the owner out of the dashboard. Audit existing users manually.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('customer')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('admin')->change();
        });
    }
};
