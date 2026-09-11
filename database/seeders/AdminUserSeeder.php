<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    /**
     * Password used when ADMIN_PASSWORD is not set. Local/testing only.
     */
    private const FALLBACK_PASSWORD = 'password';

    /**
     * Create the admin account, or promote an existing user with that email.
     *
     * Safe to re-run: an existing account keeps its password, so a password
     * changed after seeding is never reset by a later `db:seed`.
     *
     * @return void
     */
    public function run()
    {
        $config = config('auth.admin');
        $password = $config['password'] ?: $this->fallbackPassword();

        $admin = User::firstOrNew(['email' => $config['email']]);

        if (! $admin->exists) {
            $admin->name = $config['name'];
            $admin->password = Hash::make($password);
            $admin->email_verified_at = now();
        }

        $admin->role = 'admin';
        $admin->save();

        $this->command?->info("Admin account ready: {$admin->email}");
    }

    private function fallbackPassword(): string
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Set ADMIN_PASSWORD before seeding the admin account in production.');
        }

        return self::FALLBACK_PASSWORD;
    }
}
