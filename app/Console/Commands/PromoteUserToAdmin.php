<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Grants or revokes admin rights on an existing account. This is how additional
 * administrators are added once the first one exists.
 */
class PromoteUserToAdmin extends Command
{
    protected $signature = 'admin:promote
                            {email : Email address of the existing user}
                            {--demote : Revoke admin rights instead of granting them}';

    protected $description = 'Grant (or revoke) administrator rights for an existing user';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with email: {$email}");
            $this->line('To create one, run: php artisan admin:create');

            return self::FAILURE;
        }

        $demote = (bool) $this->option('demote');
        $target = $demote ? 'customer' : 'admin';

        if ($user->role === $target) {
            $this->warn("{$user->email} already has the '{$target}' role. Nothing to do.");

            return self::SUCCESS;
        }

        if ($demote && User::where('role', 'admin')->count() <= 1) {
            $this->error('Refusing to demote the last remaining administrator.');

            return self::FAILURE;
        }

        $user->update(['role' => $target]);

        $this->info($demote
            ? "{$user->email} is now a customer."
            : "{$user->email} is now an administrator.");

        return self::SUCCESS;
    }
}
