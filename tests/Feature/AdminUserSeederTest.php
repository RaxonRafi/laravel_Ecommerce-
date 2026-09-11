<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['auth.admin' => [
            'name' => 'Shop Admin',
            'email' => 'admin@example.test',
            'password' => 'secret-pass',
        ]]);
    }

    public function test_it_creates_an_admin_who_can_log_in(): void
    {
        $this->seed(AdminUserSeeder::class);

        $admin = User::where('email', 'admin@example.test')->firstOrFail();
        $this->assertSame('Shop Admin', $admin->name);
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue(Hash::check('secret-pass', $admin->password));

        $this->post(route('adminlogin'), [
            'email' => 'admin@example.test',
            'password' => 'secret-pass',
        ]);
        $this->assertAuthenticatedAs($admin);
    }

    public function test_rerunning_does_not_duplicate_or_reset_the_password(): void
    {
        $this->seed(AdminUserSeeder::class);
        User::where('email', 'admin@example.test')->update(['password' => Hash::make('changed')]);

        $this->seed(AdminUserSeeder::class);

        $this->assertSame(1, User::where('email', 'admin@example.test')->count());
        $this->assertTrue(Hash::check('changed', User::where('email', 'admin@example.test')->value('password')));
    }

    public function test_an_existing_customer_with_that_email_is_promoted(): void
    {
        $customer = User::factory()->create(['email' => 'admin@example.test', 'role' => 'customer']);

        $this->seed(AdminUserSeeder::class);

        $this->assertTrue($customer->fresh()->isAdmin());
    }

    public function test_it_falls_back_to_a_default_password_outside_production(): void
    {
        config(['auth.admin.password' => null]);

        $this->seed(AdminUserSeeder::class);

        $this->assertTrue(Hash::check('password', User::where('email', 'admin@example.test')->value('password')));
    }

    public function test_it_refuses_the_default_password_in_production(): void
    {
        config(['auth.admin.password' => null]);
        $this->app->detectEnvironment(fn () => 'production');

        $this->expectException(RuntimeException::class);

        // Run directly: `db:seed` would stop at its own production confirm prompt.
        try {
            $this->app->make(AdminUserSeeder::class)->run();
        } finally {
            $this->assertSame(0, User::count());
        }
    }
}
