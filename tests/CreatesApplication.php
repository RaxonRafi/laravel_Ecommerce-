<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $this->forceTestingEnvironment($app);

        return $app;
    }

    /**
     * Pin the environment and database for tests.
     *
     * This cannot be left to phpunit.xml. Docker Compose exports APP_ENV and
     * DB_DATABASE into the container, which land in $_SERVER, and Laravel's
     * ServerConstAdapter is consulted before the $_ENV values PHPUnit sets — so
     * the XML values are silently ignored. Overriding here happens after config
     * is loaded and is therefore authoritative.
     */
    private function forceTestingEnvironment($app): void
    {
        $app['env'] = 'testing';
        config(['app.env' => 'testing']);

        $connection = config('database.default');
        $key = "database.connections.{$connection}.database";
        $database = (string) config($key);

        if (! str_ends_with($database, '_test')) {
            config([$key => $database.'_test']);
            DB::purge($connection);
        }

        // Final guard. This must live here rather than in setUp(), because
        // RefreshDatabase wipes the database during parent::setUp() — a check
        // placed after that call runs too late to prevent anything.
        $effective = (string) config($key);

        if (! str_ends_with($effective, '_test')) {
            throw new \RuntimeException(
                "Refusing to run tests against database [{$effective}]: the name must end in '_test'.",
            );
        }
    }
}
