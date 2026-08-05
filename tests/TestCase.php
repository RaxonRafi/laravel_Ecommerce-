<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * The testing environment and database are pinned in CreatesApplication, which
     * runs before RefreshDatabase touches anything. See the note there.
     */
    use CreatesApplication;
}
