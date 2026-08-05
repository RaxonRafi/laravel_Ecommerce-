<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Bootstraps an administrator account.
 *
 * Self-registration deliberately always creates customers, so this command is
 * the only supported way to mint the first admin.
 */
class CreateAdminUser extends Command
{
    protected $signature = 'admin:create
                            {--name= : The administrator\'s name}
                            {--email= : The administrator\'s email address}
                            {--password= : The password (prompted for if omitted)}';

    protected $description = 'Create a new administrator account';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Name');
        $email = $this->option('email') ?: $this->ask('Email address');
        $password = $this->option('password') ?: $this->secret('Password (min 8 characters)');

        $validator = Validator::make(
            compact('name', 'email', 'password'),
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            if (User::where('email', $email)->exists()) {
                $this->newLine();
                $this->line("That email already exists. To grant it admin rights instead, run:");
                $this->line("  php artisan admin:promote {$email}");
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
        ]);

        $this->newLine();
        $this->info("Administrator created: {$user->email}");
        $this->line('Sign in at /admin/login');

        return self::SUCCESS;
    }
}
